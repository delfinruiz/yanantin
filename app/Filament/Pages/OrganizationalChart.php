<?php

namespace App\Filament\Pages;

use App\Models\Department;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class OrganizationalChart extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';
    
    public static function getNavigationLabel(): string
    {
        return __('organizational_chart.navigation_label');
    }

    public function getTitle(): string
    {
        return __('organizational_chart.title');
    }

    protected static string|UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.organizational-chart';

    public $ceo;
    public $departmentsTree = [];
    public $search = '';

    public function mount()
    {
        $this->loadData();
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function loadData()
    {
        // 1. Identificar al CEO (Super Admin)
        $this->ceo = User::role('super_admin')
            ->with(['employeeProfile.cargo'])
            ->first();

        // 2. Obtener Departamentos
        $departments = Department::with([
            'supervisors.employeeProfile.cargo',
            'users' => function($q) {
                $q->with(['employeeProfile.cargo', 'employeeProfile.boss']);
            }
        ])->get();

        $this->departmentsTree = $departments->map(function ($dept) {
            $deptUsers = $dept->users;
            
            // Build the recursive tree for this department
            $tree = $this->buildTree($deptUsers);

            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'tree' => $tree,
                // Mantener contadores para info rápida si es necesario, aunque no se usen visualmente
                'total_members' => $deptUsers->count(),
            ];
        });
    }

    private function buildTree(Collection $users): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        // 1. Indexar usuarios por ID
        $usersMap = $users->keyBy('id');
        $children = [];
        $roots = [];

        // 2. Agrupar por Jefe (Parent)
        foreach ($users as $user) {
            // Excluir al CEO de los árboles departamentales si aparece ahí
            if ($user->id === $this->ceo?->id) {
                continue;
            }

            $bossId = $user->employeeProfile?->reports_to;
            
            // Si tiene jefe Y el jefe está en este mismo departamento (lista de usuarios)
            if ($bossId && $usersMap->has($bossId)) {
                $children[$bossId][] = $user;
            } else {
                // Si no tiene jefe, o el jefe es de otro departamento (o es el CEO), es una raíz local
                $roots[] = $user;
            }
        }

        // 3. Ordenar raíces por jerarquía de cargo
        $roots = $this->sortByHierarchy(collect($roots));

        // 4. Construir estructura recursiva
        return $roots->map(fn($root) => $this->formatNode($root, $children));
    }

    private function formatNode($user, array &$childrenMap)
    {
        $node = $this->formatUser($user);
        
        $myChildren = isset($childrenMap[$user->id]) ? collect($childrenMap[$user->id]) : collect();
        
        // Ordenar hijos por jerarquía
        $myChildren = $this->sortByHierarchy($myChildren);

        $node['children'] = $myChildren->map(fn($child) => $this->formatNode($child, $childrenMap));

        return $node;
    }

    private function sortByHierarchy(Collection $users): Collection
    {
        return $users->sortBy(function ($user) {
            // Asumimos que hierarchy_level menor es más alto (1 = CEO, 2 = Director, etc.)
            // Si es null, lo ponemos al final
            return $user->employeeProfile?->cargo?->hierarchy_level ?? 9999;
        })->values();
    }

    private function formatUser($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->getFilamentAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&color=7F9CF5&background=EBF4FF',
            'cargo' => $user->employeeProfile?->cargo?->name ?? 'Sin Cargo',
            'reports_to' => $user->employeeProfile?->reports_to,
        ];
    }
}
