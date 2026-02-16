<?php

namespace App\Providers\Wirechat;

use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;

class ChatsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
             ->id('chats')
             ->path('chats')
             ->middleware(['web','auth'])
             ->emojiPicker()
             ->attachments()
             ->fileMimes(['zip', 'rar', 'txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'json', 'jpg', 'png', 'jpeg'])
             ->webPushNotifications()
             ->createChatAction()
             ->chatsSearch()
             ->searchableAttributes(['name', 'email'])
             ->default();
    }
}
