</div> <!-- End main-content -->

<script>
    // --- Lógica de Tema ---
    function toggleTheme() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme');
        const next = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', next);
        document.querySelector('.theme-toggle').innerText = next === 'light' ? '🌙 Modo Escuro' : '☀️ Modo Claro';
        localStorage.setItem('theme', next);
    }
    // Carregar tema salvo
    if (localStorage.getItem('theme') === 'dark') toggleTheme();

    // --- API & Dados ---
    async function checkDB() {
        const dot = document.getElementById('db-dot');
        const text = document.getElementById('db-text');
        try {
            const res = await fetch('api.php?action=check_db_status');
            const data = await res.json();
            if (data.status === 'connected') {
                dot.className = 'status-dot connected';
                text.innerText = 'MySQL Conectado';
                text.style.color = 'var(--success)';
            } else {
                throw new Error(data.message);
            }
        } catch (e) {
            dot.className = 'status-dot error';
            text.innerText = 'Erro Conexão';
            text.style.color = 'var(--danger)';
        }
    }

    async function fetchData(url) { return (await fetch(url)).json(); }
    async function postData(url, data) {
        return (await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })).json();
    }

    function filterTable(tableId, query) {
        const rows = document.querySelectorAll(`#${tableId} tbody tr`);
        query = query.toLowerCase();
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    // Inicialização comum
    window.addEventListener('load', () => {
        checkDB();
        setInterval(checkDB, 30000);
    });
</script>
</body>
</html>
