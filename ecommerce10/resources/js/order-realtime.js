import './echo';

let subscribedOrderId = null;

function subscribeToOrderUpdates() {
    const orderRoot = document.querySelector('[data-order-realtime]');
    const orderId = orderRoot?.dataset.orderRealtime;

    if (!orderId) {
        if (subscribedOrderId) {
            window.Echo.leave(`orders.${subscribedOrderId}`);
            subscribedOrderId = null;
        }

        return;
    }

    if (subscribedOrderId === orderId) return;

    if (subscribedOrderId) {
        window.Echo.leave(`orders.${subscribedOrderId}`);
    }

    subscribedOrderId = orderId;

    window.Echo.private(`orders.${orderId}`).listen(
        '.order.status.updated',
        async () => {
            try {
                const response = await fetch(window.location.href, {
                    headers: {
                        Accept: 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) throw new Error('Unable to refresh order details.');

                const html = await response.text();
                const responseDocument = new DOMParser().parseFromString(html, 'text/html');
                const updatedContent = responseDocument.querySelector('#order-detail-content');
                const currentContent = document.querySelector('#order-detail-content');

                if (updatedContent && currentContent) {
                    currentContent.replaceWith(updatedContent);
                    document.dispatchEvent(new Event('boost:page-ready'));
                    document.dispatchEvent(new CustomEvent('order:realtime-updated'));
                }
            } catch (error) {
                console.error('Failed to refresh realtime order details:', error);
                window.location.reload();
            }
        },
    );
}

document.addEventListener('DOMContentLoaded', subscribeToOrderUpdates);
document.addEventListener('boost:page-ready', subscribeToOrderUpdates);
subscribeToOrderUpdates();
