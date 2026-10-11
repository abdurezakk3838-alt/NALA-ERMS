(function () {
    'use strict';

    // The PHP session stores the language; this shared layer translates the
    // visible interface after every page render (including AJAX content).
    const translations = {
        am: {
            'Dashboard': 'ዳሽቦርድ', 'Archive': 'ማህደር', 'Incoming Letters': 'የገቢ ደብዳቤዎች',
            'Outgoing Letters': 'የወጪ ደብዳቤዎች', 'Internal Memos': 'የውስጥ ማስታወሻዎች',
            'Records': 'መዝገቦች', 'Workflow': 'የስራ ሂደት', 'Search': 'ፍለጋ',
            'Notifications': 'ማሳወቂያዎች', 'User Management': 'የተጠቃሚ አስተዳደር',
            'Audit Trail': 'የኦዲት መዝገብ', 'Reports': 'ሪፖርቶች', 'Settings': 'ቅንብሮች',
            'Logout': 'ውጣ', 'Main': 'ዋና', 'Management': 'አስተዳደር', 'Admin': 'አስተዳደሪ', 'System': 'ስርዓት',
            'Incoming Records': 'የገቢ መዝገቦች', 'Outgoing Records': 'የወጪ መዝገቦች',
            'Search Letters & Memos': 'ደብዳቤዎችን እና ማስታወሻዎችን ፈልግ',
            'Pending Letters': 'በመጠባበቅ ላይ ያሉ ደብዳቤዎች', 'Recent Records': 'የቅርብ ጊዜ መዝገቦች',
            'Incoming': 'ገቢ', 'Outgoing': 'ወጪ', 'Registered': 'የተመዘገበ', 'In Process': 'በሂደት ላይ',
            'Pending': 'በመጠባበቅ ላይ', 'Completed': 'ተጠናቋል', 'Sender': 'ላኪ', 'Receiver': 'ተቀባይ',
            'Title': 'ርዕስ', 'Status': 'ሁኔታ', 'Date': 'ቀን', 'Actions': 'ተግባራት', 'Action': 'ተግባር',
            'Type': 'ዓይነት', 'Reference': 'ማጣቀሻ', 'Reference Number': 'የማጣቀሻ ቁጥር',
            'Search records': 'መዝገቦችን ፈልግ', 'Clear': 'አጽዳ', 'Results': 'ውጤቶች',
            'Close': 'ዝጋ', 'Cancel': 'ሰርዝ', 'Save & Send': 'አስቀምጥ እና ላክ', 'Open': 'ክፈት',
            'No incoming letters found.': 'ምንም የገቢ ደብዳቤ አልተገኘም።',
            'No outgoing letters found.': 'ምንም የወጪ ደብዳቤ አልተገኘም።',
            'No recent records found.': 'ምንም የቅርብ ጊዜ መዝገብ አልተገኘም።',
            'No notifications yet.': 'እስካሁን ማሳወቂያ የለም።', 'English': 'እንግሊዝኛ', 'Afan Oromo': 'አፋን ኦሮሞ'
        },
        om: {
            'Dashboard': 'Daashboordii', 'Archive': 'Kuusaa', 'Incoming Letters': 'Xalayaalee Galan',
            'Outgoing Letters': 'Xalayaalee Ba’an', 'Internal Memos': 'Yaadannoo Keessaa',
            'Records': 'Galmeewwan', 'Workflow': 'Adeemsa Hojii', 'Search': 'Barbaadi',
            'Notifications': 'Beeksisa', 'User Management': 'Bulchiinsa Fayyadamtootaa',
            'Audit Trail': 'Galmee Odiitii', 'Reports': 'Gabaasa', 'Settings': 'Qindaa’ina',
            'Logout': 'Ba’i', 'Main': 'Guddaa', 'Management': 'Bulchiinsa', 'Admin': 'Bulchaa', 'System': 'Sirna',
            'Incoming Records': 'Galmeewwan Galan', 'Outgoing Records': 'Galmeewwan Ba’an',
            'Search Letters & Memos': 'Xalayaalee fi Yaadannoo Barbaadi', 'Pending Letters': 'Xalayaalee Eegamaa Jiran',
            'Recent Records': 'Galmeewwan Dhihoo', 'Incoming': 'Galaa', 'Outgoing': 'Ba’aa', 'Registered': 'Galmaa’e',
            'In Process': 'Adeemsa Irra', 'Pending': 'Eegamaa Jira', 'Completed': 'Xumurame', 'Sender': 'Ergaa',
            'Receiver': 'Fudhaa', 'Title': 'Mata-duree', 'Status': 'Haala', 'Date': 'Guyyaa', 'Actions': 'Gochaalee',
            'Action': 'Gocha', 'Type': 'Gosa', 'Reference': 'Wabii', 'Reference Number': 'Lakkoofsa Wabii',
            'Search records': 'Galmeewwan Barbaadi', 'Clear': 'Haqi', 'Results': 'Bu’aawwan', 'Close': 'Cufi',
            'Cancel': 'Haqi', 'Save & Send': 'Olkaa’i fi Ergi', 'Open': 'Bani',
            'No incoming letters found.': 'Xalayaa galuu tokko illee hin argamne.',
            'No outgoing letters found.': 'Xalayaa ba’uu tokko illee hin argamne.',
            'No recent records found.': 'Galmeen dhihoo tokko illee hin argamne.',
            'No notifications yet.': 'Ammaaf beeksisni hin jiru.', 'English': 'Ingiliffa', 'Afan Oromo': 'Afaan Oromoo'
        }
    };

    function translate(root) {
        const dictionary = translations[document.documentElement.dataset.language || 'en'];
        if (!dictionary) return;
        (root.querySelectorAll ? root.querySelectorAll('*') : []).forEach((element) => {
            if (element.children.length === 0) {
                const key = element.textContent.trim();
                if (dictionary[key] && element.textContent.trim() !== dictionary[key]) element.textContent = dictionary[key];
            }
            ['placeholder', 'title', 'aria-label'].forEach((attribute) => {
                const key = element.getAttribute(attribute);
                if (key && dictionary[key]) element.setAttribute(attribute, dictionary[key]);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        translate(document);
        new MutationObserver((mutations) => mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => { if (node.nodeType === 1) translate(node); });
        })).observe(document.body, { childList: true, subtree: true });
    });
}());
