const cartKey = document.body.dataset.cartKey;

if (cartKey) {
    const cartPage = document.querySelector('[data-client-cart]');
    const cartCount = document.querySelector('[data-cart-count]');
    const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    function readCart() {
        const value = localStorage.getItem(cartKey);
        if (value === null && cartPage) {
            const legacy = JSON.parse(cartPage.dataset.legacyCart);
            localStorage.setItem(cartKey, JSON.stringify(legacy));
            return legacy;
        }
        const items = value === null ? [] : JSON.parse(value);
        if (!Array.isArray(items)) throw new Error('Não foi possível ler o carrinho neste navegador.');
        return items;
    }

    function saveCart(items) {
        localStorage.setItem(cartKey, JSON.stringify(items));
        updateCount(items);
    }

    function updateCount(items) {
        if (!cartCount) return;
        const count = items.reduce((total, item) => total + item.quantity, 0);
        cartCount.textContent = count;
        cartCount.setAttribute('aria-label', `${count} itens`);
    }

    document.querySelectorAll('[data-add-to-cart]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const feedback = form.closest('.product-item').querySelector('[data-product-feedback]');
            try {
                const items = readCart();
                const id = Number(form.elements.product_id.value);
                const quantity = Number(form.elements.quantity.value || 1);
                const existing = items.find((item) => item.id === id);
                if (!Number.isInteger(quantity) || quantity < 1 || quantity + (existing?.quantity || 0) > 99) {
                    feedback.textContent = 'O limite por produto é 99 unidades.';
                    return;
                }
                if (existing) {
                    existing.quantity += quantity;
                    existing.price_cents = Number(form.dataset.priceCents);
                    existing.name = form.dataset.productName;
                } else {
                    items.push({ id, name: form.dataset.productName, price_cents: Number(form.dataset.priceCents), quantity });
                }
                saveCart(items);
                feedback.textContent = 'Produto adicionado ao carrinho.';
            } catch {
                feedback.textContent = 'Não foi possível salvar o carrinho neste navegador.';
            }
        });
    });

    if (cartPage) {
        const list = cartPage.querySelector('[data-client-cart-items]');
        const error = cartPage.querySelector('[data-client-cart-error]');
        const checkout = cartPage.querySelector('[data-client-checkout]');
        const button = checkout.querySelector('button[type="submit"]');

        function renderCart() {
            const items = readCart();
            cartPage.querySelector('[data-client-cart-empty]').hidden = items.length > 0;
            cartPage.querySelector('[data-client-cart-filled]').hidden = items.length === 0;
            list.replaceChildren();
            items.forEach((item) => {
                const row = document.createElement('li');
                row.className = 'cart-item';
                const details = document.createElement('div');
                const name = document.createElement('h2');
                name.className = 'cart-item__name';
                name.textContent = item.name;
                const price = document.createElement('p');
                price.className = 'cart-item__price';
                price.textContent = `${item.quantity} × ${money.format(item.price_cents / 100)}`;
                details.append(name, price);
                const total = document.createElement('strong');
                total.textContent = money.format(item.price_cents * item.quantity / 100);
                const actions = document.createElement('div');
                actions.className = 'cart-item__actions';
                const label = document.createElement('label');
                label.textContent = `Quantidade de ${item.name}`;
                label.className = 'sr-only';
                label.htmlFor = `cart-quantity-${item.id}`;
                const input = document.createElement('input');
                input.type = 'number'; input.min = '1'; input.max = '99'; input.required = true;
                input.value = item.quantity; input.className = 'quantity-input'; input.id = label.htmlFor;
                const update = document.createElement('button');
                update.type = 'button'; update.className = 'text-button'; update.textContent = 'Atualizar';
                update.addEventListener('click', () => {
                    if (!input.reportValidity()) return;
                    try {
                        const current = readCart();
                        const target = current.find((entry) => entry.id === item.id);
                        if (target) target.quantity = Number(input.value);
                        saveCart(current); error.textContent = ''; renderCart();
                    } catch { error.textContent = 'Não foi possível salvar o carrinho neste navegador.'; }
                });
                const remove = document.createElement('button');
                remove.type = 'button'; remove.className = 'text-button'; remove.textContent = 'Remover';
                remove.setAttribute('aria-label', `Remover ${item.name}`);
                remove.addEventListener('click', () => {
                    try { saveCart(readCart().filter((entry) => entry.id !== item.id)); error.textContent = ''; renderCart(); }
                    catch { error.textContent = 'Não foi possível salvar o carrinho neste navegador.'; }
                });
                actions.append(label, input, update, remove);
                row.append(details, total, actions);
                list.append(row);
            });
            cartPage.querySelector('[data-client-cart-total]').textContent = money.format(items.reduce((sum, item) => sum + item.price_cents * item.quantity, 0) / 100);
        }

        try {
            renderCart();
            document.querySelectorAll('[data-server-cart]').forEach((section) => { section.hidden = true; });
            cartPage.hidden = false;
        } catch {
            // Keep the server-rendered fallback available if browser storage is unavailable.
        }

        checkout.addEventListener('submit', async (event) => {
            event.preventDefault();
            button.disabled = true;
            error.textContent = '';
            try {
                const items = readCart();
                const response = await fetch(checkout.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': checkout.elements._token.value },
                    body: JSON.stringify({ items: items.map((item) => ({ product_id: item.id, quantity: item.quantity, price_cents: item.price_cents })), notes: checkout.elements.notes.value }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors?.cart?.[0] || data.message || 'Não foi possível confirmar o pedido.');
                try { saveCart([]); } catch { /* The order was accepted; still open its tracking page. */ }
                window.location.assign(data.url);
            } catch (problem) {
                error.textContent = problem instanceof TypeError ? 'Não foi possível conectar. Tente novamente.' : problem.message;
            } finally { button.disabled = false; }
        });
        window.addEventListener('storage', (event) => { if (event.key === cartKey) renderCart(); });
    } else {
        try { updateCount(readCart()); } catch { /* The form reports storage errors when used. */ }
        window.addEventListener('storage', (event) => { if (event.key === cartKey) updateCount(readCart()); });
    }
}
