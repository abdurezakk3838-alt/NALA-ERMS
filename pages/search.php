<?php
// pages/search.php
?>

<div class="mb-5">
    <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-search text-blue-600 mr-2"></i>Search Letters &amp; Memos</h2>
    <p class="text-sm text-gray-500 mt-1">Find records by reference number, sender unique number, sender, receiver, keywords, title, or description.</p>
</div>

<form id="recordSearchForm" class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-5">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2">
            <label for="searchQuery" class="block text-xs font-semibold text-gray-600 mb-1">Search term</label>
            <input id="searchQuery" type="search" required autocomplete="off" placeholder="Enter text, name, title, or number" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="searchField" class="block text-xs font-semibold text-gray-600 mb-1">Search by</label>
            <select id="searchField" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all">All available fields</option>
                <option value="reference_number">Reference number</option>
                <option value="sender_unique_number">Sender unique number</option>
                <option value="sender_name">Sender name</option>
                <option value="receiver_name">Receiver name</option>
                <option value="keywords">Keywords</option>
                <option value="title">Letter title</option>
                <option value="description">Description / subject</option>
            </select>
        </div>
        <div>
            <label for="searchType" class="block text-xs font-semibold text-gray-600 mb-1">Record type</label>
            <select id="searchType" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all">All records</option>
                <option value="incoming">Incoming letters</option>
                <option value="outgoing">Outgoing letters</option>
                <option value="internal">Internal memos</option>
            </select>
        </div>
    </div>
    <div class="mt-4 flex items-center gap-3">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold"><i class="fas fa-search mr-2"></i>Search records</button>
        <button type="reset" id="clearSearch" class="text-sm text-gray-600 hover:text-gray-900 px-3 py-2">Clear</button>
    </div>
</form>

<div class="flex items-center justify-between mb-3">
    <h3 class="font-semibold text-gray-800">Results</h3>
    <span id="searchCount" class="text-sm text-gray-500" aria-live="polite">Enter a search term to begin.</span>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Type</th><th>Reference #</th><th>Sender Unique #</th><th>Title</th><th>Sender</th><th>Receiver</th><th>Keywords</th><th>Date</th><th>Action</th>
            </tr>
        </thead>
        <tbody id="searchResults">
            <tr><td colspan="9" class="text-center text-gray-400 py-8">Use the form above to search records.</td></tr>
        </tbody>
    </table>
</div>

<script>
(() => {
    const form = document.getElementById('recordSearchForm');
    const results = document.getElementById('searchResults');
    const count = document.getElementById('searchCount');
    const cell = (value) => {
        const td = document.createElement('td');
        td.textContent = value || '—';
        return td;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const query = document.getElementById('searchQuery').value.trim();
        if (!query) return;
        count.textContent = 'Searching…';
        results.innerHTML = '<tr><td colspan="9" class="text-center text-gray-400 py-8">Searching records…</td></tr>';
        try {
            const params = new URLSearchParams({
                query,
                field: document.getElementById('searchField').value,
                type: document.getElementById('searchType').value
            });
            const response = await fetch('api/search.php?' + params.toString());
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Search failed.');
            results.innerHTML = '';
            count.textContent = `${data.results.length} record${data.results.length === 1 ? '' : 's'} found`;
            if (!data.results.length) {
                results.innerHTML = '<tr><td colspan="9" class="text-center text-gray-400 py-8">No matching records found.</td></tr>';
                return;
            }
            data.results.forEach((record) => {
                const row = document.createElement('tr');
                row.append(cell(record.doc_type), cell(record.ref_number), cell(record.sender_unique_num), cell(record.title), cell(record.sender_name), cell(record.receiver_name), cell(record.keywords), cell(record.received_date || record.date));
                const action = document.createElement('td');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-2 py-1 rounded-lg transition';
                button.innerHTML = '<i class="fas fa-eye"></i> View';
                button.addEventListener('click', () => viewLetter(record.ref_number, record.doc_type === 'Incoming Letter' ? 'incoming' : record.doc_type === 'Outgoing Letter' ? 'outgoing' : 'internal'));
                action.append(button); row.append(action); results.append(row);
            });
        } catch (error) {
            count.textContent = 'Search could not be completed.';
            results.innerHTML = '<tr><td colspan="9" class="text-center text-red-500 py-8">Unable to search records. Please try again.</td></tr>';
            console.error(error);
        }
    });

    document.getElementById('clearSearch').addEventListener('click', () => {
        results.innerHTML = '<tr><td colspan="9" class="text-center text-gray-400 py-8">Use the form above to search records.</td></tr>';
        count.textContent = 'Enter a search term to begin.';
    });
})();
</script>
