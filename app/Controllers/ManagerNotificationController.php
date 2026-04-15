<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\NotificationRepository;
use App\Repositories\RoleRepository;

final class ManagerNotificationController
{
    public function index(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        $managerUserId = is_array($managerUser) ? (int) ($managerUser['id'] ?? 0) : 0;
        $caserneId = $this->resolveManagerCaserneId();
        $filters = [
            'lu' => isset($_GET['lu']) ? (string) $_GET['lu'] : 'all',
            'event_code' => isset($_GET['event_code']) ? (string) $_GET['event_code'] : '',
        ];

        $repository = new NotificationRepository();
        $notificationsAvailable = $repository->isAvailable();
        $eventCatalog = NotificationRepository::eventCatalog();
        $history = $repository->findHistoryForUser($managerUserId, $caserneId, $filters, 200);
        $unreadCount = $repository->getUnreadCount($managerUserId, $caserneId);
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';

        require dirname(__DIR__, 2) . '/public/views/manager_notifications.php';
    }

    public function markRead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_notifications&action=index');
        }

        $managerUser = $_SESSION['manager_user'] ?? null;
        $managerUserId = is_array($managerUser) ? (int) ($managerUser['id'] ?? 0) : 0;
        $notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;

        $repository = new NotificationRepository();
        $ok = $repository->markAsRead($managerUserId, $notificationId);

        $this->redirect('/index.php?controller=manager_notifications&action=index' . ($ok ? '&success=read' : '&error=read'));
    }

    public function markAllRead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_notifications&action=index');
        }

        $managerUser = $_SESSION['manager_user'] ?? null;
        $managerUserId = is_array($managerUser) ? (int) ($managerUser['id'] ?? 0) : 0;
        $caserneId = $this->resolveManagerCaserneId();

        $repository = new NotificationRepository();
        $ok = $repository->markAllAsRead($managerUserId, $caserneId);

        $this->redirect('/index.php?controller=manager_notifications&action=index' . ($ok ? '&success=read_all' : '&error=read'));
    }

    public function settings(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        $caserneId = $this->resolveManagerCaserneId();
        $repository = new NotificationRepository();
        $notificationSettings = $repository->readAdminSettings($caserneId);
        $channels = [
            'in_app_enabled' => true,
            'email_enabled' => false,
        ];
        $firstEvent = array_key_first($notificationSettings);
        if ($firstEvent !== null && isset($notificationSettings[$firstEvent])) {
            $channels['in_app_enabled'] = (bool) ($notificationSettings[$firstEvent]['in_app_enabled'] ?? true);
            $channels['email_enabled'] = (bool) ($notificationSettings[$firstEvent]['email_enabled'] ?? false);
        }

        $roleRepository = new RoleRepository();
        $roles = $roleRepository->isAvailable() ? $roleRepository->findAll() : [];
        if ($roles === []) {
            $roles = [
                ['code' => 'admin', 'nom' => 'Administrateur'],
                ['code' => 'responsable_materiel', 'nom' => 'Responsable materiel'],
                ['code' => 'resp_pharma', 'nom' => 'Responsable Pharmacie'],
                ['code' => 'verificateur', 'nom' => 'Verificateur'],
            ];
        }

        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $eventCatalog = NotificationRepository::eventCatalog();

        require dirname(__DIR__, 2) . '/public/views/manager_notification_settings.php';
    }

    public function settingsSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_notifications&action=settings');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_notifications&action=settings&error=invalid');
        }

        $eventCatalog = NotificationRepository::eventCatalog();
        $enabledByEvent = [];
        $rolesByEvent = [];
        foreach ($eventCatalog as $eventCode => $_meta) {
            $eventKey = str_replace('.', '_', $eventCode);
            $enabledByEvent[$eventCode] = isset($_POST['event_enabled'][$eventKey]) && (string) $_POST['event_enabled'][$eventKey] === '1';
            $rawRoles = is_array($_POST['event_roles'][$eventKey] ?? null) ? $_POST['event_roles'][$eventKey] : [];
            $rolesByEvent[$eventCode] = array_values(array_map('strval', $rawRoles));
        }
        $inAppEnabled = isset($_POST['channel_in_app_enabled']) && (string) $_POST['channel_in_app_enabled'] === '1';
        $emailEnabled = isset($_POST['channel_email_enabled']) && (string) $_POST['channel_email_enabled'] === '1';

        $repository = new NotificationRepository();
        $ok = $repository->saveAdminSettings($caserneId, $inAppEnabled, $emailEnabled, $enabledByEvent, $rolesByEvent);

        $this->redirect('/index.php?controller=manager_notifications&action=settings' . ($ok ? '&success=saved' : '&error=save'));
    }

    public function preferencesSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager&action=account');
        }

        $managerUser = $_SESSION['manager_user'] ?? null;
        $managerUserId = is_array($managerUser) ? (int) ($managerUser['id'] ?? 0) : 0;
        if ($managerUserId <= 0) {
            $this->redirect('/index.php?controller=manager_auth&action=login_form&error=session_expired');
        }

        $catalog = NotificationRepository::eventCatalog();
        $inAppByEvent = [];
        foreach ($catalog as $eventCode => $_meta) {
            $eventKey = str_replace('.', '_', $eventCode);
            $inAppByEvent[$eventCode] = isset($_POST['notif_in_app'][$eventKey]) && (string) $_POST['notif_in_app'][$eventKey] === '1';
        }

        $repository = new NotificationRepository();
        $ok = $repository->saveSubscriptionsByUser($managerUserId, $inAppByEvent);
        $this->redirect('/index.php?controller=manager&action=account' . ($ok ? '&updated_notif=1' : '&error=notif_save_failed'));
    }

    private function resolveManagerCaserneId(): ?int
    {
        $caserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;

        return $caserneId > 0 ? $caserneId : null;
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
