<?php
// index.php
require_once 'includes/functions.php';
requireLogin();

$current_page = $_GET['page'] ?? 'dashboard';
$page_title = ucfirst(str_replace('_', ' ', $current_page));

// Check permission
if (!hasPermission($current_page)) {
    $current_page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['language'] ?? 'en'); ?>" data-language="<?php echo htmlspecialchars($_SESSION['language'] ?? 'en'); ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NALA Enhanced Records Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <!-- Toast -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle text-green-400"></i>
        <div>
            <span id="toastMessage" class="font-semibold text-sm">Action Completed</span>
            <br />
            <span id="toastSubMessage" class="text-xs text-gray-300"></span>
        </div>
    </div>

    <!-- Main App -->
    <div id="appMain">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'includes/header.php'; ?>
            
            <div class="p-6" id="pageContainer">
                <?php 
                $page_file = 'pages/' . $current_page . '.php';
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    include 'pages/dashboard.php';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php include 'includes/modals.php'; ?>

    <!-- AI Assistant -->
    <div id="assistantPanel" class="assistant-panel" aria-hidden="true">
        <div class="assistant-header">
            <div><i class="fas fa-robot mr-2"></i>NALA AI Assistant</div>
            <select id="assistantLanguage" aria-label="Assistant response language" title="Assistant response language">
                <option value="auto">Auto detect</option>
                <option value="en">English</option>
                <option value="am">አማርኛ</option>
                <option value="om">Afaan Oromoo</option>
            </select>
            <button type="button" id="assistantCloseBtn" aria-label="Close AI Assistant">&times;</button>
        </div>
        <div id="assistantMessages" class="assistant-messages">
            <?php
            $assistantWelcome = [
                'en' => 'Hello! Ask me about any NALA ERMS feature. I can explain letters, memos, reviews, archive, search, workflow, reports, and settings.',
                'am' => 'ሰላም! ስለ ማንኛውም የNALA ERMS ባህሪ ይጠይቁኝ። ደብዳቤዎችን፣ ማስታወሻዎችን፣ ግምገማን፣ ማህደርን፣ ፍለጋን፣ የስራ ሂደትን፣ ሪፖርቶችንና ቅንብሮችን አስረዳለሁ።',
                'om' => 'Akkam! Waa’ee amala NALA ERMS kamiyyuu na gaafadhu. Xalayaalee, yaadannoowwan, sakatta’a, kuusaa, barbaacha, adeemsa hojii, gabaasa fi qindaa’ina nan ibsa.',
            ];
            echo htmlspecialchars($assistantWelcome[$_SESSION['language'] ?? 'en'] ?? $assistantWelcome['en'], ENT_QUOTES, 'UTF-8');
            ?>
        </div>
        <form id="assistantForm" class="assistant-form">
            <input id="assistantQuestion" type="text" placeholder="Ask a question..." autocomplete="off" required />
            <button type="submit" aria-label="Send question"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>

    <script src="assets/js/i18n.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
    (() => {
        const panel = document.getElementById('assistantPanel');
        const form = document.getElementById('assistantForm');
        const input = document.getElementById('assistantQuestion');
        const messages = document.getElementById('assistantMessages');
        const language = document.getElementById('assistantLanguage');
        const addMessage = (text, fromUser) => {
            const message = document.createElement('div');
            message.className = 'assistant-message ' + (fromUser ? 'assistant-message-user' : 'assistant-message-bot');
            message.textContent = text;
            messages.appendChild(message);
            messages.scrollTop = messages.scrollHeight;
        };
        document.getElementById('assistantToggleBtn')?.addEventListener('click', () => {
            panel.classList.toggle('active');
            panel.setAttribute('aria-hidden', panel.classList.contains('active') ? 'false' : 'true');
            if (panel.classList.contains('active')) input.focus();
        });
        document.getElementById('assistantCloseBtn')?.addEventListener('click', () => panel.classList.remove('active'));
        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const question = input.value.trim();
            if (!question) return;
            addMessage(question, true);
            input.value = '';
            try {
                const response = await fetch('api/assistant.php', {
                    method: 'POST',
                    body: new URLSearchParams({ question, language: language.value })
                });
                const data = await response.json();
                addMessage(data.success ? data.answer : (data.message || 'I could not answer that.'), false);
            } catch (error) {
                addMessage('The assistant is temporarily unavailable.', false);
            }
        });
    })();
    </script>
</body>
</html>
