import './bootstrap';
import 'emoji-picker-element';
document.addEventListener('DOMContentLoaded', () => {
    const updateSurveyCounter = async () => {
        try {
            const el = document.getElementById('survey-send-count');
            if (!el) return;
            const res = await fetch('/admin/api/pending-surveys-count', { credentials: 'include' });
            const data = await res.json();
            if (typeof data.count === 'number') {
                // Busca el elemento que muestra el número dentro del Stat
                // Fallback: establece texto del elemento directamente
                el.innerText = String(data.count);
            }
        } catch (e) {
            // Silenciar errores
        }
    };
    updateSurveyCounter();
    setInterval(updateSurveyCounter, 30000);
});
