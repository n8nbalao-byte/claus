<?php
$page_title = "Memória de Contatos";
require_once 'header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: var(--text-secondary);">Base de Contatos</h3>
        <input type="text" onkeyup="filterTable('contact-table', this.value)" placeholder="🔍 Buscar contato..." style="width: 250px; margin-bottom: 0;">
    </div>
    <table id="contact-table">
        <thead><tr><th>Nome</th><th>Telefone</th><th>Relação</th><th>Notas</th></tr></thead>
        <tbody></tbody>
    </table>
</div>

<script>
    async function loadContacts() {
        const contacts = await fetchData('api.php?action=get_contacts');
        const tbody = document.querySelector('#contact-table tbody');
        tbody.innerHTML = '';
        contacts.forEach(c => {
            tbody.innerHTML += `<tr>
                <td><b>${c.name}</b></td>
                <td>${c.phone_number}</td>
                <td><span class="role-badge">${c.relationship || 'N/A'}</span></td>
                <td>${c.notes || '-'}</td>
            </tr>`;
        });
    }

    window.addEventListener('load', () => {
        loadContacts();
    });
</script>

<?php require_once 'footer.php'; ?>
