import './cart.js';

const tableOrders = document.querySelector('[data-table-orders-url]');

if (tableOrders) {
    const list = tableOrders.querySelector('[data-orders-list]');
    const message = tableOrders.querySelector('[data-orders-message]');
    let timer;
    let running = false;

    function renderOrders(orders) {
        const existing = new Map([...list.querySelectorAll('[data-order-id]')].map((item) => [item.dataset.orderId, item]));
        const empty = list.querySelector('.table-orders__empty');

        if (orders.length === 0) {
            existing.forEach((item) => item.remove());
            if (!empty) {
                const placeholder = document.createElement('p');
                placeholder.className = 'table-orders__empty';
                placeholder.textContent = 'Ainda não há pedidos para esta mesa.';
                list.append(placeholder);
            }
            return;
        }

        empty?.remove();
        orders.forEach((order, index) => {
            let item = existing.get(order.id);
            if (!item) {
                item = document.createElement('a');
                item.className = 'table-orders__item';
                item.dataset.orderId = order.id;
                const details = document.createElement('span');
                details.className = 'table-orders__details';
                details.append(document.createElement('strong'), document.createElement('span'));
                const status = document.createElement('span');
                status.className = 'table-orders__status';
                const action = document.createElement('span');
                action.className = 'table-orders__action';
                action.textContent = 'Acompanhar →';
                item.append(details, status, action);
            }
            item.href = order.url;
            item.querySelector('strong').textContent = order.id;
            item.querySelector('.table-orders__details > span').textContent = order.placed_at;
            item.querySelector('.table-orders__status').textContent = order.status;
            if (list.children[index] !== item) list.insertBefore(item, list.children[index] ?? null);
            existing.delete(order.id);
        });
        existing.forEach((item) => item.remove());
    }

    async function refreshOrders() {
        if (document.hidden || running) return;
        running = true;
        try {
            const response = await fetch(tableOrders.dataset.tableOrdersUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('Falha ao atualizar pedidos');
            const data = await response.json();
            renderOrders(data.orders);
            message.textContent = 'Os status são atualizados automaticamente.';
        } catch {
            message.textContent = 'Não foi possível atualizar os pedidos. Tentaremos novamente.';
        } finally {
            running = false;
            if (!document.hidden) timer = setTimeout(refreshOrders, 15000);
        }
    }

    timer = setTimeout(refreshOrders, 15000);
    document.addEventListener('visibilitychange', () => {
        clearTimeout(timer);
        if (!document.hidden) refreshOrders();
    });
}
