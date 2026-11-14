let subscribedUserId = null;

async function subscribeToNotifications() {
    const trigger = document.querySelector('[data-realtime-notifications]');
    const userId = trigger?.dataset.notificationUser;

    if (!userId) {
        if (subscribedUserId && window.Echo) {
            window.Echo.leave(`App.Models.User.${subscribedUserId}`);
            subscribedUserId = null;
        }
        return;
    }

    if (subscribedUserId === userId) return;

    const { default: initializeEcho } = await import('./echo');
    initializeEcho();
    subscribedUserId = userId;

    window.Echo.private(`App.Models.User.${userId}`).notification((notification) => {
        document.querySelectorAll('[data-notification-count]').forEach((badge) => {
            const count = Number.parseInt(badge.textContent, 10) || 0;
            badge.textContent = String(Math.min(count + 1, 99));
            badge.classList.remove('hidden');
        });
        document.dispatchEvent(new CustomEvent('app:notification-received', { detail: notification }));
    });
}

document.addEventListener('DOMContentLoaded', subscribeToNotifications);
document.addEventListener('boost:page-ready', subscribeToNotifications);
subscribeToNotifications();
