<?php
// In-app help assistant for NALA ERMS.
require_once '../includes/functions.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$question = trim($_POST['question'] ?? '');
if ($question === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter a question.']);
    exit;
}

$questionLower = mb_strtolower($question, 'UTF-8');
$requestedLanguage = $_POST['language'] ?? 'auto';
$supportedLanguages = ['auto', 'en', 'am', 'om'];
if (!in_array($requestedLanguage, $supportedLanguages, true)) {
    echo json_encode(['success' => false, 'message' => 'Unsupported assistant language.']);
    exit;
}

$language = $requestedLanguage;
if ($language === 'auto') {
    if (preg_match('/[\x{1200}-\x{137F}]/u', $question)) {
        $language = 'am';
    } else {
        $words = preg_split('/[^\p{L}\p{N}]+/u', $questionLower, -1, PREG_SPLIT_NO_EMPTY);
        $oromoWords = ['akkam', 'akkamitti', 'maal', 'maali', 'xalayaa', 'xalayaalee', 'galmee', 'galmeewwan', 'kuusaa', 'barbaadi', 'fayyadamaa', 'fayyadamtoota', 'hojii', 'sirna', 'eessa', 'eenyu', 'fooyyessi', 'dabali', 'ergaa', 'guyyaa', 'afaan', 'oromoo'];
        if (array_intersect($words, $oromoWords)) {
            $language = 'om';
        } else {
            $language = 'en';
        }
    }
}

$hasAny = static function (array $terms) use ($questionLower): bool {
    foreach ($terms as $term) {
        if (strpos($questionLower, mb_strtolower($term, 'UTF-8')) !== false) return true;
    }
    return false;
};

$intents = [
    'greeting' => ['hello', 'hi ', 'hey', 'good morning', 'good afternoon', 'good evening', 'ሰላም', 'እንዴት ነው'],
    'language' => ['change language', 'select language', 'assistant language', 'language setting', 'translate', 'afaan oromo', 'afaan Oromoo', 'አማርኛ', 'ቋንቋ', 'afaan'],
    'stats' => ['how many', 'count', 'number of', 'statistics', 'stats', 'summary', 'meeqa', 'lakkoofsa', 'hangam', 'ስንት', 'ብዛት', 'ስታቲስቲክስ'],
    'received_time' => ['received time', 'time received', 'letter time', 'date and time', 'yeroo fudhatame', 'sa’aatii', 'yeroo xalayaa', 'የተቀበለበት ሰዓት', 'የተቀበለበት ጊዜ'],
    'overview' => ['about the project', 'about project', 'what is this project', 'about the system', 'describe the system', 'system overview', 'project overview', 'all project', 'what can this system do', 'what is erms', 'what is nala erms', 'about erms', 'about nala erms', 'ስለ ስርዓቱ', 'ስለ ፕሮጀክቱ', 'waa’ee sirna', 'sirna maalidha'],
    'dashboard' => ['dashboard', 'home page', 'main page', 'daashboordii', 'ዳሽቦርድ', 'ዋና ገጽ'],
    'create_incoming' => ['add incoming', 'new incoming', 'create incoming', 'register incoming', 'incoming letter', 'incoming', 'galaa', 'xalayaa galuu', 'xalayaa galaa', 'የገቢ ደብዳቤ', 'ገቢ ደብዳቤ', 'የገቢ'],
    'create_outgoing' => ['add outgoing', 'new outgoing', 'create outgoing', 'register outgoing', 'outgoing letter', 'outgoing', 'ba’aa', 'xalayaa ba’uu', 'xalayaa ba’aa', 'የወጪ ደብዳቤ', 'ወጪ ደብዳቤ', 'የወጪ'],
    'create_internal' => ['internal memo', 'internal', 'memo', 'yaadannoo', 'yaadannoo keessaa', 'የውስጥ ማስታወሻ', 'ውስጣዊ ማስታወሻ'],
    'review' => ['review', 'approve', 'approval', 'reject', 'rejection', 'sign', 'signature', 'miratuu', 'diduu', 'raggaas', 'ማጽደቅ', 'አጽድቅ', 'ውድቅ', 'ገምግም', 'ፊርማ'],
    'delete_archive' => ['delete archive', 'delete archived', 'remove archive', 'delete archived letter', 'kuusaa haqi', 'ማህደር ሰርዝ', 'የተመዘገበውን ሰርዝ'],
    'delete_record' => ['delete letter', 'delete document', 'remove letter', 'remove document', 'xalayaa haqi', 'galmee haqi', 'ሰነድ ሰርዝ', 'ደብዳቤ ሰርዝ'],
    'archive' => ['archive', 'archived', 'preserve', 'records management', 'kuusaa', 'kuusaa galmee', 'kuusaa galmeewwan', 'ማህደር', 'ማህደር ውስጥ'],
    'dispatch' => ['dispatch', 'dispatched', 'send approved', 'dispatch letter', 'ergi', 'erguu', 'xalayaa ergi', 'ላክ', 'ማስተላለፍ', 'ማስረከብ'],
    'search' => ['search', 'find', 'locate', 'barbaadi', 'barbaaduu', 'eessaa barbaada', 'ፈልግ', 'ፍለጋ'],
    'audit' => ['audit', 'history', 'who edited', 'edited by', 'activity', 'odiitii', 'seenaa', 'galmee gocha', 'ኦዲት', 'ታሪክ', 'ማን አስተካከለ'],
    'notification' => ['notification', 'notified', 'alert', 'beeksisa', 'beeksisawwan', 'ማሳወቂያ', 'ማስታወቂያ'],
    'report' => ['report', 'reporting', 'gabaasa', 'gabaasawwan', 'ሪፖርት', 'ሪፖርቶች'],
    'workflow' => ['workflow', 'workflow rule', 'routing', 'adeemsa hojii', 'seera hojii', 'የስራ ሂደት', 'የስራ ደንብ'],
    'user_settings' => ['user', 'account', 'password', 'profile', 'role', 'settings', 'users', 'fayyadamaa', 'fayyadamtoota', 'jecha icciitii', 'qindaa', 'የተጠቃሚ', 'ተጠቃሚዎች', 'የይለፍ ቃል', 'ቅንብር', 'ሚና'],
    'classification' => ['classification', 'classify', 'category', 'security level', 'confidential', 'restricted', 'top secret', 'ramaddii', 'sadarkaa nageenyaa', 'iccitii', 'ምደባ', 'የደህንነት ደረጃ', 'ሚስጥራዊ'],
    'edit' => ['edit', 'update', 'change', 'modify', 'fooyyessi', 'jijjiiri', 'አስተካክል', 'አዘምን', 'ቀይር'],
    'incoming_list' => ['incoming letters', 'incoming letter', 'incoming', 'xalayaalee galan', 'xalayaa galaa', 'xalayaa galuu', 'የገቢ ደብዳቤዎች', 'ገቢ ደብዳቤ', 'የገቢ'],
    'outgoing_list' => ['outgoing letters', 'outgoing letter', 'outgoing', 'xalayaalee ba’an', 'xalayaa ba’aa', 'xalayaa ba’uu', 'የወጪ ደብዳቤዎች', 'ወጪ ደብዳቤ', 'የወጪ'],
    'internal_list' => ['internal memo', 'internal memos', 'memo', 'yaadannoowwan keessaa', 'yaadannoo keessaa', 'የውስጥ ማስታወሻዎች', 'የውስጥ ማስታወሻ'],
    'view' => ['view', 'open', 'attachment', 'document', 'ilaali', 'bani', 'galmee', 'ክፈት', 'አይ', 'ሰነድ', 'አባሪ'],
];

$isGreeting = preg_match('/^(hello|hi|hey|good morning|good afternoon|good evening|akkam|nagaa|ሰላም|እንዴት ነው)[\s!,.?]*$/u', $questionLower);
$intent = $isGreeting ? 'greeting' : 'help';
$creationRequest = $hasAny([
    'add', 'new', 'create', 'register', 'receive a', 'galmees', 'dabali', 'uumi',
    'መዝግብ', 'መመዝገብ', 'አዲስ', 'ፍጠር', 'አስገባ', 'መጨመር', 'መፍጠር',
]);
foreach ($intents as $candidate => $terms) {
    if ($candidate === 'greeting') continue;
    if ($hasAny($terms)) {
        if (strpos($candidate, 'create_') === 0 && !$creationRequest) continue;
        $intent = $candidate;
        break;
    }
}

$answers = [
    'greeting' => [
        'en' => 'Hello! I am the NALA ERMS Assistant. Ask me how to use any system module, manage letters and memos, follow approvals, search records, or understand reports and settings.',
        'am' => 'ሰላም! እኔ የNALA ERMS ረዳት ነኝ። ስለ ማንኛውም የስርዓቱ ክፍል፣ ደብዳቤዎችና ማስታወሻዎች፣ ማጽደቅ፣ ፍለጋ፣ ሪፖርቶች ወይም ቅንብሮች ይጠይቁኝ።',
        'om' => 'Akkam! Ani Gargaaraa NALA ERMS ti. Waa’ee kutaa sirnichaa, xalayaalee fi yaadannoowwan, mirkaneessa, barbaacha, gabaasa ykn qindaa’ina na gaafadhu.',
    ],
    'language' => [
        'en' => 'You can choose the application language from the language menu in the top bar. In this assistant, use the language menu in the assistant header to select Auto, English, Amharic, or Afaan Oromoo. Auto detects Amharic from its script and Afaan Oromoo from common words; otherwise it replies in English.',
        'am' => 'የመተግበሪያውን ቋንቋ ከላይ ባለው የቋንቋ ምናሌ መቀየር ይችላሉ። በዚህ ረዳት ራስጌ ውስጥ አውቶ፣ እንግሊዝኛ፣ አማርኛ ወይም አፋን ኦሮሞ ይምረጡ። አውቶ አማርኛን ከፊደሉ እና አፋን ኦሮሞን ከተለመዱ ቃላት ይለያል።',
        'om' => 'Afaan sirnichaa jijjiiruuf filannoo Afaan barruu gubbaa irraa fayyadami. Gargaaraa kana keessatti mata-duree isaa irraa Ofumaan, Ingiliffa, Amaariffa ykn Afaan Oromoo filadhu. Ofumaan qubee Amaariffaa fi jechoota Afaan Oromoo beekamoo adda baasa; yoo hin taane Ingiliffaan deebisa.',
    ],
    'dashboard' => [
        'en' => 'The Dashboard is the home page after sign-in. It summarizes record activity and recent or pending documents that your role can access. Use the sidebar to open Incoming Letters, Outgoing Letters, Internal Memos, Search, Archive, Reports, or other permitted modules.',
        'am' => 'ዳሽቦርዱ ከገቡ በኋላ የሚከፈተው ዋና ገጽ ነው። የመዝገብ እንቅስቃሴን፣ የቅርብ ጊዜ ወይም በመጠባበቅ ላይ ያሉ ሰነዶችን በሚናዎ መሰረት ያጠቃልላል። ሌሎች የተፈቀዱ ክፍሎችን ለመክፈት የጎን ምናሌውን ይጠቀሙ።',
        'om' => 'Daashboordiin fuula jalqabaa erga seentee booda mul’atu. Sochii galmee fi galmeewwan haaraa ykn eegamaa jiran kan gaheen kee arguu danda’u cuunfa. Kutaa biroo hayyamame banuuf cinaacha fayyadami.',
    ],
    'overview' => [
        'en' => "NALA ERMS (National Archive and Library Services Enhanced Records Management System) supports the document lifecycle:\n\n1. Register incoming letters, outgoing letters, and internal memos, with reference details and attachments.\n2. Route records to a receiver and send notifications.\n3. Let authorized reviewers inspect, remark, sign, approve, or reject.\n4. Track status and actions through search, notifications, workflow, QR codes, and the Audit Trail.\n5. Preserve completed decisions in the Archive.\n6. Generate reports for authorized roles.\n\nAvailable pages and actions depend on your role. Use the sidebar to navigate. The assistant explains system use but does not disclose private letter contents.",
        'am' => "NALA ERMS (የብሔራዊ ማህደርና ቤተ-መጻሕፍት አገልግሎት የተሻሻለ የመዝገብ አስተዳደር ስርዓት) የሰነድ ሂደትን ያስተዳድራል፦\n\n1. የገቢና የወጪ ደብዳቤዎችን እና የውስጥ ማስታወሻዎችን ከማጣቀሻ ዝርዝርና አባሪዎች ጋር መመዝገብ።\n2. መዝገቦችን ለተቀባይ ማስተላለፍ እና ማሳወቂያ መላክ።\n3. የተፈቀደላቸው ገምጋሚዎች ሰነዶችን እንዲመለከቱ፣ አስተያየት እንዲሰጡ፣ እንዲፈርሙ፣ እንዲያጸድቁ ወይም ውድቅ እንዲያደርጉ ማስቻል።\n4. ሁኔታና ተግባራትን በፍለጋ፣ ማሳወቂያ፣ የስራ ሂደት፣ QR ኮድ እና ኦዲት መከታተል።\n5. የተጠናቀቁ ውሳኔዎችን በማህደር ማስቀመጥ።\n6. ለተፈቀዱ ሚናዎች ሪፖርቶችን ማዘጋጀት።\n\nየሚታዩ ገጾችና ተግባራት በሚናዎ ይወሰናሉ። ረዳቱ ስርዓቱን መጠቀም ያስረዳል እንጂ የግል ደብዳቤ ይዘትን አያሳይም።",
        'om' => "NALA ERMS (Sirna Bulchiinsa Galmeewwan Kuusaa fi Maktabaa Biyyaalessaa) adeemsa galmee guutuu ni deeggera:\n\n1. Xalayaalee galan, xalayaalee ba’an fi yaadannoowwan keessaa lakkoofsa wabii fi maxxansa waliin galmeessi.\n2. Galmee fudhataa bira geessi; beeksisa ergi.\n3. Sakatta’oonni hayyamaman galmee ilaalu, yaada kennu, mallatteessu, mirkaneessu ykn diduu danda’u.\n4. Haala fi gochaalee barbaacha, beeksisa, adeemsa hojii, QR koodii fi Galmee Odiitii fayyadamuun hordofi.\n5. Murtii xumurame Kuusaa keessatti kaa’i.\n6. Gabaasa qopheessi; kun hayyama gahee irratti hundaa’a.\n\nFuulli fi gochi argamu gahee fayyadamaa irratti hundaa’a. Gargaaraan itti fayyadama sirnichaa ibsa; qabiyyee dhuunfaa xalayaa hin mul’isu.",
    ],
    'create_incoming' => [
        'en' => "To register an incoming letter:\n1. Open Incoming Letters and select New Incoming Letter.\n2. Enter its reference, sender number, title, sender, receiver, and received date and time.\n3. Add the description, classification, security level, keywords, and attachment as applicable.\n4. Verify the receiver and details, then choose Save & Send.\n\nThe record is submitted as pending and the receiver is notified. Reference numbers must be unique.",
        'am' => "የገቢ ደብዳቤ ለመመዝገብ፦\n1. የገቢ ደብዳቤዎችን ይክፈቱና አዲስ የገቢ ደብዳቤን ይምረጡ።\n2. የማጣቀሻ ቁጥር፣ የላኪ ቁጥር፣ ርዕስ፣ ላኪ፣ ተቀባይ፣ የተቀበለበትን ቀንና ሰዓት ያስገቡ።\n3. አስፈላጊ ከሆነ መግለጫ፣ ምደባ፣ የደህንነት ደረጃ፣ ቁልፍ ቃላትና አባሪ ያክሉ።\n4. ተቀባዩንና ዝርዝሩን አረጋግጠው አስቀምጥ እና ላክን ይጫኑ።\n\nመዝገቡ በመጠባበቅ ሁኔታ ይመዘገባል፣ ተቀባዩም ማሳወቂያ ይደርሰዋል። የማጣቀሻ ቁጥር ልዩ መሆን አለበት።",
        'om' => "Xalayaa galuu galmeessuuf:\n1. Xalayaalee Galan bani; Xalayaa Galaa Haaraa filadhu.\n2. Lakkoofsa wabii, lakkoofsa ergaa, mata-duree, ergaa, fudhataa, guyyaa fi yeroo fudhatame galchi.\n3. Yoo barbaachise ibsa, ramaddii, sadarkaa nageenyaa, jechoota furtuu fi maxxansa dabali.\n4. Odeeffannoo fi fudhataa mirkaneessi; Olkaa’i fi Ergi cuqaasi.\n\nGalmeen haala eegamaa jiruun galmaa’a; fudhataan beeksifama. Lakkoofsi wabii adda ta’uu qaba.",
    ],
    'create_outgoing' => [
        'en' => "To register an outgoing letter:\n1. Open Outgoing Letters and select New Outgoing Letter.\n2. Enter the reference, title, sender, receiver, date, and required details.\n3. Add classification, security level, priority, keywords, and attachment as needed.\n4. Verify the recipient and choose Save & Send.\n\nThe letter enters the approval workflow. After approval, an authorized records user can dispatch it.",
        'am' => "የወጪ ደብዳቤ ለመመዝገብ፦\n1. የወጪ ደብዳቤዎችን ይክፈቱና አዲስ የወጪ ደብዳቤን ይምረጡ።\n2. የማጣቀሻ ቁጥር፣ ርዕስ፣ ላኪ፣ ተቀባይ፣ ቀንና አስፈላጊ ዝርዝሮችን ያስገቡ።\n3. እንደ አስፈላጊነቱ ምደባ፣ የደህንነት ደረጃ፣ ቅድሚያ፣ ቁልፍ ቃላትና አባሪ ያክሉ።\n4. ተቀባዩን አረጋግጠው አስቀምጥ እና ላክን ይጫኑ።\n\nደብዳቤው ወደ ማጽደቅ ሂደት ይገባል። ከጸደቀ በኋላ የተፈቀደለት የመዝገብ ተጠቃሚ ሊልከው ይችላል።",
        'om' => "Xalayaa ba’u galmeessuuf:\n1. Xalayaalee Ba’an bani; Xalayaa Ba’aa Haaraa filadhu.\n2. Lakkoofsa wabii, mata-duree, ergaa, fudhataa, guyyaa fi odeeffannoo barbaachisu galchi.\n3. Yoo barbaachise ramaddii, sadarkaa nageenyaa, dursa, jechoota furtuu fi maxxansa dabali.\n4. Fudhataa mirkaneessi; Olkaa’i fi Ergi cuqaasi.\n\nXalayaan adeemsa mirkaneessa keessa seena. Erga mirkanaa’ee booda hojjetaan galmee hayyamame ni erga.",
    ],
    'create_internal' => [
        'en' => "To create an internal memo:\n1. Open Internal Memos and select New Internal Memo.\n2. Enter the reference, date, title, sender, receiver, and memo details.\n3. Add an attachment if needed and verify the recipient.\n4. Choose Save & Send to route it for review.\n\nThe memo appears in the workflow according to your role and the assigned receiver.",
        'am' => "የውስጥ ማስታወሻ ለመፍጠር፦\n1. የውስጥ ማስታወሻዎችን ይክፈቱና አዲስ የውስጥ ማስታወሻን ይምረጡ።\n2. የማጣቀሻ ቁጥር፣ ቀን፣ ርዕስ፣ ላኪ፣ ተቀባይና የማስታወሻውን ዝርዝር ያስገቡ።\n3. ካስፈለገ አባሪ ያክሉና ተቀባዩን ያረጋግጡ።\n4. ለግምገማ ለማስተላለፍ አስቀምጥ እና ላክን ይጫኑ።\n\nማስታወሻው በሚናዎና በተመደበው ተቀባይ መሰረት በስራ ሂደቱ ይታያል።",
        'om' => "Yaadannoo keessaa uumuuf:\n1. Yaadannoowwan Keessaa bani; Yaadannoo Haaraa filadhu.\n2. Lakkoofsa wabii, guyyaa, mata-duree, ergaa, fudhataa fi ibsa yaadannoo galchi.\n3. Yoo barbaachise maxxansa dabali; fudhataa mirkaneessi.\n4. Adeemsa sakatta’aaf dabarsuuf Olkaa’i fi Ergi cuqaasi.\n\nYaadannoon gahee keetii fi fudhataa ramadame irratti hundaa’uun adeemsa hojii keessatti mul’ata.",
    ],
    'incoming_list' => [
        'en' => 'Open Incoming Letters from the sidebar to see records available to your account. The list includes the reference, sender, receiver, status, received date and time, and available actions. Select View to open a record; use Search to filter by reference, sender, receiver, title, keyword, date, or status.',
        'am' => 'ከጎን ምናሌው የገቢ ደብዳቤዎችን ይክፈቱ። ዝርዝሩ ለመለያዎ የተፈቀዱ መዝገቦችን፣ የማጣቀሻ ቁጥር፣ ላኪ፣ ተቀባይ፣ ሁኔታ፣ የተቀበለበት ቀንና ሰዓት እና የሚገኙ ተግባራትን ያሳያል። መዝገብ ለመክፈት አይንን ይምረጡ፤ በፍለጋ ማጣሪያ ያድርጉ።',
        'om' => 'Xalayaalee Galan cinaacha irraa bani; galmeewwan akkaawuntii keetiif hayyamaman ni argita. Tarreen lakkoofsa wabii, ergaa, fudhataa, haala, guyyaa fi yeroo fudhatame akkasumas gochaalee jiraatan agarsiisa. Galmee banuuf Ilaali filadhu; barbaacha fayyadamuun calali.',
    ],
    'outgoing_list' => [
        'en' => 'Open Outgoing Letters from the sidebar to see records available to your account. Select View to inspect a letter. Pending letters assigned to you may show Review; approved letters may show Dispatch to authorized users. Search can filter the list.',
        'am' => 'ለመለያዎ የተፈቀዱ መዝገቦችን ለማየት ከጎን ምናሌው የወጪ ደብዳቤዎችን ይክፈቱ። ደብዳቤን ለማየት አይንን ይምረጡ። ለእርስዎ የተመደቡ በመጠባበቅ ላይ ያሉ ደብዳቤዎች ገምግምን ሊያሳዩ ይችላሉ፤ የጸደቁት ደግሞ ለተፈቀደላቸው ተጠቃሚዎች ላክን ሊያሳዩ ይችላሉ።',
        'om' => 'Galmeewwan akkaawuntii keetiif hayyamaman ilaaluuf Xalayaalee Ba’an bani. Xalayaa ilaaluuf Ilaali filadhu. Xalayaaleen eegamaa jiran kan siif ramadaman Sakatta’i agarsiisuu danda’u; kanneen mirkanaa’an Ergi agarsiisuu danda’u.',
    ],
    'internal_list' => [
        'en' => 'Open Internal Memos from the sidebar. Select View to read a memo and open its attachment. Pending memos assigned to you may show Review, and Edit appears only when your role and the memo status allow it.',
        'am' => 'ከጎን ምናሌው የውስጥ ማስታወሻዎችን ይክፈቱ። ማስታወሻን ለማንበብና አባሪውን ለመክፈት አይንን ይምረጡ። ለእርስዎ የተመደቡ በመጠባበቅ ላይ ያሉ ማስታወሻዎች ገምግምን ሊያሳዩ ይችላሉ፤ አስተካክል የሚታየው ሚናዎና ሁኔታው ሲፈቅዱ ብቻ ነው።',
        'om' => 'Yaadannoowwan Keessaa cinaacha irraa bani. Yaadannoo dubbisuu fi maxxansa isaa banuuf Ilaali filadhu. Yaadannoowwan eegamaa jiran kan siif ramadaman Sakatta’i agarsiisuu danda’u; Fooyyessi kan mul’atu gaheen kee fi haalli yaadannoo yoo hayyame qofa.',
    ],
    'review' => [
        'en' => "To review a letter or memo:\n1. Open its record list and select Review on a pending record assigned to you.\n2. Read the details and open any attachment.\n3. Add a clear remark when needed and provide your digital signature if requested.\n4. Choose Approve or Reject and confirm.\n\nThe decision and review details are recorded. If Review is unavailable, check that you are the assigned receiver and that the document is still pending. Avoid submitting the same decision twice.",
        'am' => "ደብዳቤ ወይም ማስታወሻ ለመገምገም፦\n1. የመዝገቡን ዝርዝር ይክፈቱና ለእርስዎ በተመደበ በመጠባበቅ ላይ ባለ መዝገብ ላይ ገምግምን ይምረጡ።\n2. ዝርዝሩን ያንብቡና አባሪውን ይክፈቱ።\n3. ካስፈለገ ግልጽ አስተያየት ይስጡና ዲጂታል ፊርማ ያድርጉ።\n4. አጽድቅ ወይም ውድቅ አድርግን ይምረጡና ያረጋግጡ።\n\nውሳኔውና የግምገማ ዝርዝሮቹ ይመዘገባሉ። የግምገማ አማራጩ ካልታየ፣ እርስዎ የተመደበው ተቀባይ መሆንዎንና ሰነዱ በመጠባበቅ ሁኔታ መሆኑን ያረጋግጡ።",
        'om' => "Xalayaa ykn yaadannoo sakatta’uuf:\n1. Tarree galmee bani; galmee eegamaa jiru kan siif ramadame irratti Sakatta’i filadhu.\n2. Odeeffannoo dubbisi; maxxansa yoo jiraate bani.\n3. Yoo barbaachise yaada ifa ta’e kenni; mallattoo dijitaalaa gaafame kenni.\n4. Mirkaneessi ykn Didi filadhu; mirkaneessi.\n\nMurtiin fi odeeffannoon sakatta’aa ni galmaa’u. Sakatta’i yoo hin jirre, fudhataa ramadame ta’uu kee fi galmeen ammallee eegamaa jiraachuu isaa mirkaneessi.",
    ],
    'archive' => [
        'en' => 'The Archive preserves documents that have completed approval or rejection. Open Archive from the sidebar, search by reference, title, or status, and open a record to inspect its saved details and attachment. Access and delete actions depend on your role; deleting an archived copy does not mean the original active record is deleted.',
        'am' => 'ማህደሩ የማጽደቅ ወይም ውድቅ ሂደት የተጠናቀቁ ሰነዶችን ያስቀምጣል። ከጎን ምናሌው ማህደርን ይክፈቱ፣ በማጣቀሻ፣ ርዕስ ወይም ሁኔታ ይፈልጉ፣ ከዚያም የተቀመጠውን ዝርዝርና አባሪ ይመልከቱ። መዳረሻና የመሰረዝ ተግባር በሚናዎ ይወሰናሉ፤ በማህደር ያለ ቅጂ መሰረዝ ዋናው ንቁ መዝገብ ተሰርዟል ማለት አይደለም።',
        'om' => 'Kuusaan galmeewwan murtiin mirkaneessuu ykn diduu xumurame ni kaa’a. Kuusaa bani; lakkoofsa wabii, mata-duree ykn haala irratti barbaadi; galmee baniitii odeeffannoo fi maxxansa isaa ilaali. Hayyamni fi haqi gahee kee irratti hundaa’a. Koppii kuusaa haqachuun galmee jalqabaa sirna keessaa haquu jechuu miti.',
    ],
    'delete_archive' => [
        'en' => 'If your role allows it, open Archive and choose Delete on the archived copy, then confirm. This removes the archived snapshot only; it does not remove the original incoming, outgoing, or internal record. If Delete is unavailable, your account does not have the required permission.',
        'am' => 'ሚናዎ ከፈቀደ፣ ማህደርን ክፈቱና በተቀመጠው ቅጂ ላይ ሰርዝን ይጫኑ፣ ከዚያም ያረጋግጡ። ይህ በማህደር ያለውን ቅጂ ብቻ ያስወግዳል፤ ዋናውን የገቢ፣ የወጪ ወይም የውስጥ መዝገብ አይሰርዝም። የመሰረዝ አማራጩ ካልታየ መለያዎ ፈቃድ የለውም።',
        'om' => 'Gaheen kee yoo hayyame, Kuusaa bani; koppii kuusaa irratti Haqi cuqaasiitii mirkaneessi. Kun koppii kuusaa qofa haqa; galmee jalqabaa galaa, ba’aa ykn keessaa hin haqu. Haqi yoo hin mul’anne, akkaawuntiin kee hayyama hin qabu.',
    ],
    'delete_record' => [
        'en' => 'Deleting an active incoming, outgoing, or internal record is different from deleting its archived copy. The available action depends on your role, permissions, and the document status. Use only an authorized delete action; if it is not shown, contact your administrator. Deleting an archive copy does not delete the active record.',
        'am' => 'ንቁ የገቢ፣ የወጪ ወይም የውስጥ መዝገብን መሰረዝ በማህደር ያለውን ቅጂ ከመሰረዝ የተለየ ነው። የሚገኘው ተግባር በሚናዎ፣ በፈቃድዎና በሰነዱ ሁኔታ ይወሰናል። የተፈቀደውን ተግባር ብቻ ይጠቀሙ፤ ካልታየ አስተዳዳሪን ያነጋግሩ።',
        'om' => 'Galmee galaa, ba’aa ykn keessaa sirna keessatti jiru haquun koppii Kuusaa keessaa haqurraa adda. Gochi jiru gahee, hayyama fi haala galmee irratti hundaa’a. Gochaa hayyamame qofa fayyadami; yoo hin mul’anne bulchaa qunnami. Koppii Kuusaa haqachuun galmee jalqabaa hin haqu.',
    ],
    'dispatch' => [
        'en' => 'Dispatch applies to approved outgoing letters. Open Outgoing Letters, locate the approved record, and use Dispatch if the action is available to your role. Confirm the destination and follow any on-screen steps. If the button is missing, check the letter status and your permissions.',
        'am' => 'ማስረከብ የሚመለከተው ለጸደቁ የወጪ ደብዳቤዎች ነው። የወጪ ደብዳቤዎችን ይክፈቱ፣ የጸደቀውን መዝገብ ያግኙና ሚናዎ ከፈቀደ ላክን ይጠቀሙ። መድረሻውን ያረጋግጡና በስክሪኑ ላይ የሚታዩትን እርምጃዎች ይከተሉ።',
        'om' => 'Erguun xalayaalee ba’an kanneen mirkanaa’an irratti hojjeta. Xalayaalee Ba’an bani; galmee mirkanaa’e barbaadi; gaheen kee yoo hayyame Ergi fayyadami. Bakka itti ergamu mirkaneessiitii tarkaanfiiwwan fuula irratti mul’atan hordofi.',
    ],
    'search' => [
        'en' => 'Open Search from the sidebar. Enter a reference, title, sender, receiver, keyword, or description; choose Incoming, Outgoing, Internal, Archive, or All; apply filters and open a result to view its details. Results are limited by your access permissions.',
        'am' => 'ከጎን ምናሌው ፍለጋን ይክፈቱ። የማጣቀሻ ቁጥር፣ ርዕስ፣ ላኪ፣ ተቀባይ፣ ቁልፍ ቃል ወይም መግለጫ ያስገቡ፤ ገቢ፣ ወጪ፣ ውስጥ፣ ማህደር ወይም ሁሉንም ይምረጡ፤ ማጣሪያዎችን ይጠቀሙና ዝርዝሩን ለማየት ውጤት ይክፈቱ። ውጤቶች በመዳረሻ ፈቃድዎ ይወሰናሉ።',
        'om' => 'Barbaadi jedhu cinaacha irraa bani. Wabii, mata-duree, ergaa, fudhataa, jecha furtuu ykn ibsa galchi; Galaa, Ba’aa, Keessaa, Kuusaa ykn Hunda filadhu; calaltuu fayyadamiitii bu’aa tokko bani. Bu’aawwan hayyama kee irratti hundaa’u.',
    ],
    'audit' => [
        'en' => 'Open Audit Trail to review recorded actions, users, dates, and details. It helps authorized users trace who performed system actions and when. The available audit information depends on your role.',
        'am' => 'የተመዘገቡ ተግባራትን፣ ተጠቃሚዎችን፣ ቀኖችንና ዝርዝሮችን ለመመልከት የኦዲት መዝገብን ይክፈቱ። ይህ ፈቃድ ያላቸው ተጠቃሚዎች ማን መቼ ምን እንዳደረገ እንዲከታተሉ ይረዳል።',
        'om' => 'Gochaalee galmaa’an, fayyadamtoota, guyyaa fi odeeffannoo ilaaluuf Galmee Odiitii bani. Fayyadamtoonni hayyamaman eenyu yeroo kamitti maal akka hojjetes ni hordofu. Odeeffannoon gahee kee irratti hundaa’a.',
    ],
    'notification' => [
        'en' => 'Open Notifications from the top bar to see messages sent to your account. They can announce new records and workflow decisions. Select a notification to follow its related record; notification access follows your account permissions.',
        'am' => 'ወደ መለያዎ የተላኩ መልዕክቶችን ለማየት ከላይ ያለውን ማሳወቂያዎች ይክፈቱ። አዳዲስ መዝገቦችንና የስራ ሂደት ውሳኔዎችን ሊያሳውቁ ይችላሉ። ተዛማጅ መዝገብን ለመክፈት ማሳወቂያውን ይምረጡ።',
        'om' => 'Ergaawwan akkaawuntii keetti ergame ilaaluuf barruu gubbaa irraa Beeksisa bani. Galmeewwan haaraa fi murtii adeemsa hojii ni beeksisu. Galmee walqabatu hordofuuf beeksisa filadhu.',
    ],
    'report' => [
        'en' => 'Open Reports and choose an available report, such as Daily Register, Monthly Summary, Workflow Performance, or Compliance. Set the requested filters and generate the report. Report options depend on your role.',
        'am' => 'ሪፖርቶችን ይክፈቱና እንደ ዕለታዊ መዝገብ፣ ወርሃዊ ማጠቃለያ፣ የስራ ሂደት አፈጻጸም ወይም የተገዢነት ሪፖርት ያለ አማራጭ ይምረጡ። የሚፈለጉትን ማጣሪያዎች አድርገው ሪፖርቱን ያዘጋጁ።',
        'om' => 'Gabaasa baniitii gabaasa jiru, fakkeenyaaf Galmee Guyyaa, Cuunfaa Ji’aa, Raawwii Adeemsa Hojii ykn Gabaasa Compliance filadhu. Calaltuu barbaachisu kaa’ii gabaasa qopheessi. Filannoowwan gahee kee irratti hundaa’u.',
    ],
    'workflow' => [
        'en' => 'Workflow routes records through the actions allowed for their type and current status. Open Workflow if your role permits it to review configured rules. The responsible receiver reviews pending documents; approved outgoing letters may then be dispatched. Role and document status control available actions.',
        'am' => 'የስራ ሂደት መዝገቦችን በሰነዱ አይነትና በአሁኑ ሁኔታ መሰረት በተፈቀዱ እርምጃዎች ያስተላልፋል። ሚናዎ ከፈቀደ የተዋቀሩ ደንቦችን ለማየት የስራ ሂደትን ይክፈቱ። ተቀባዩ በመጠባበቅ ላይ ያሉ ሰነዶችን ይገመግማል፤ የጸደቁ የወጪ ደብዳቤዎች ሊላኩ ይችላሉ።',
        'om' => 'Adeemsi hojii galmee gosa isaa fi haala ammaa irratti hundaa’uun tarkaanfii hayyamame keessa dabarsa. Gaheen kee yoo hayyame, seera qindaa’e ilaaluuf Adeemsa Hojii bani. Fudhataan galmee eegamaa jiru sakatta’a; xalayaa ba’aa mirkanaa’e erguun ni danda’ama.',
    ],
    'user_settings' => [
        'en' => 'Open Settings to update your own profile, contact details, photo, or password. Users and role administration are available only to authorized administrators. Your role determines which pages and actions you can access; contact an administrator if you need a permission change.',
        'am' => 'የራስዎን መገለጫ፣ የመገኛ ዝርዝር፣ ፎቶ ወይም የይለፍ ቃል ለማዘመን ቅንብሮችን ይክፈቱ። የተጠቃሚና ሚና አስተዳደር ለተፈቀደላቸው አስተዳዳሪዎች ብቻ ነው። ፈቃድ ለመቀየር አስተዳዳሪን ያነጋግሩ።',
        'om' => 'Profaayila, odeeffannoo quunnamtii, suuraa ykn jecha icciitii kee fooyyessuuf Qindaa’ina bani. Bulchiinsi fayyadamtootaa fi gahee bulchitoota hayyamamaniif qofa. Hayyama jijjiiruuf bulchaa qunnami.',
    ],
    'classification' => [
        'en' => 'Classification describes a record’s subject category; the available categories appear in the document form. Security level controls how sensitive a document is (for example Public, Official, Confidential, Restricted, or Top Secret). Choose values according to your organization’s records and information-security policy. Access remains role-controlled.',
        'am' => 'ምደባ የመዝገቡን የጉዳይ ምድብ ይገልጻል፤ የሚገኙ ምድቦች በሰነድ ቅጹ ይታያሉ። የደህንነት ደረጃ የሰነዱን ሚስጥራዊነት ያመለክታል (ለምሳሌ ህዝባዊ፣ ይፋዊ፣ ሚስጥራዊ፣ የተገደበ ወይም ከፍተኛ ሚስጥር)። በድርጅቱ ፖሊሲ መሰረት ይምረጡ።',
        'om' => 'Ramaddiin gosa dhimma galmee ibsa; filannoon unka galmee irratti mul’ata. Sadarkaan nageenyaa iccitii galmee agarsiisa (fakkeenyaaf Uummataaf, Official, Iccitii, Daangeffame ykn Iccitii Guddaa). Imaammata dhaabbata keetii hordofii filadhu.',
    ],
    'received_time' => [
        'en' => 'When registering an incoming letter, enter the Date Received and Time Received in the form. Time Received defaults to your system’s current local time and can be adjusted to the actual arrival time. Both are saved with the incoming record.',
        'am' => 'የገቢ ደብዳቤ ሲመዘግቡ በቅጹ ውስጥ የተቀበለበትን ቀንና ሰዓት ያስገቡ። ሰዓቱ በኮምፒውተርዎ የአካባቢ ሰዓት ይጀምራል፤ ደብዳቤው በትክክል የደረሰበትን ሰዓት ለማስገባት ሊቀየር ይችላል። ሁለቱም ከመዝገቡ ጋር ይቀመጣሉ።',
        'om' => 'Yeroo xalayaa galuu galmeessitu, Guyyaa Fudhatame fi Yeroo Fudhatame unka keessatti galchi. Yeroon Fudhatame yeroo ammaa sirna kompiitaraa keetii irraa jalqaba; yeroo dhugaa xalayichi itti ga’e galchuuf jijjiiruu dandeessa. Lamaan galmee waliin olkaa’amu.',
    ],
    'edit' => [
        'en' => 'To edit a record, open its incoming, outgoing, or internal list and choose Edit when available. Change permitted fields and save. Editing is restricted by role and workflow status; completed documents may no longer be editable. If Edit is missing, check your permissions or ask an administrator.',
        'am' => 'መዝገብን ለማስተካከል የገቢ፣ የወጪ ወይም የውስጥ ዝርዝሩን ይክፈቱና ካለ አስተካክልን ይምረጡ። የተፈቀዱ መስኮችን ቀይረው ያስቀምጡ። ማስተካከያ በሚናና በስራ ሂደት ሁኔታ ይገደባል፤ የተጠናቀቁ ሰነዶች ሊታረሙ አይችሉም።',
        'om' => 'Galmee fooyyessuuf tarree Galaa, Ba’aa ykn Keessaa bani; yoo jiraate Fooyyessi filadhu. Dirree hayyamame jijjiiriitii olkaa’i. Fooyya’iinsi gahee fi haala adeemsa hojii irratti hundaa’a; galmeen xumurame fooyya’uu dhiisuu danda’a.',
    ],
    'stats' => [
        'en' => "Current system totals:\n- Incoming letters: %d\n- Outgoing letters: %d\n- Internal memos: %d\n- Documents waiting for review: %d\n\nThese are aggregate counts only; they do not reveal private document contents.",
        'am' => "የአሁኑ የስርዓት ጠቅላላ ቁጥሮች፦\n- የገቢ ደብዳቤዎች፦ %d\n- የወጪ ደብዳቤዎች፦ %d\n- የውስጥ ማስታወሻዎች፦ %d\n- ግምገማ የሚጠብቁ ሰነዶች፦ %d\n\nእነዚህ ጠቅላላ ቁጥሮች ብቻ ናቸው፤ የግል ሰነዶችን ይዘት አያሳዩም።",
        'om' => "Lakkoofsa waliigalaa sirna ammaa:\n- Xalayaalee galan: %d\n- Xalayaalee ba’an: %d\n- Yaadannoowwan keessaa: %d\n- Galmeewwan sakatta’a eegaa jiran: %d\n\nKun lakkoofsa waliigalaa qofa; qabiyyee galmee dhuunfaa hin mul’isu.",
    ],
    'view' => [
        'en' => 'Open the relevant record list and select View, or use its QR code if available. The details window shows information that your account is permitted to see; open an attachment from the record view. If you cannot see a record, access may be restricted by role.',
        'am' => 'የሚመለከተውን የመዝገብ ዝርዝር ይክፈቱና አይን ይምረጡ፣ ወይም ካለ QR ኮዱን ይጠቀሙ። የዝርዝር መስኮቱ መለያዎ እንዲያይ የተፈቀደለትን መረጃ ያሳያል፤ አባሪውን ከዚያ ይክፈቱ።',
        'om' => 'Tarree galmee barbaachisu baniitii Ilaali filadhu; ykn yoo jiraate QR koodii fayyadami. Fuulli odeeffannoo akkaawuntiin kee ilaaluuf hayyamame agarsiisa; maxxansa galmee keessaa bani. Galmee yoo hin argine, gaheen kee daangessuu danda’a.',
    ],
    'help' => [
        'en' => 'I can answer questions about NALA ERMS modules and how to use them: incoming and outgoing letters, internal memos, approvals and rejection, archive, dispatch, search, notifications, reports, workflow, audit trail, user settings, classification, security levels, and received date/time. Please ask a specific question about a system feature. I do not have access to private letter contents.',
        'am' => 'ስለ NALA ERMS ክፍሎችና አጠቃቀማቸው መልስ መስጠት እችላለሁ፦ የገቢና የወጪ ደብዳቤዎች፣ የውስጥ ማስታወሻዎች፣ ማጽደቅና ውድቅ ማድረግ፣ ማህደር፣ መላክ፣ ፍለጋ፣ ማሳወቂያ፣ ሪፖርቶች፣ የስራ ሂደት፣ ኦዲት፣ የተጠቃሚ ቅንብሮች፣ ምደባ፣ የደህንነት ደረጃና የተቀበለበት ቀን/ሰዓት። ስለ አንድ የስርዓት ባህሪ በተለይ ይጠይቁ። የግል ደብዳቤዎችን ማየት አልችልም።',
        'om' => 'Waa’ee kutaa NALA ERMS fi itti fayyadama isaanii deebisuu nan danda’a: xalayaalee galan/ba’an, yaadannoowwan keessaa, mirkaneessuu/diduu, kuusaa, erguu, barbaacha, beeksisa, gabaasa, adeemsa hojii, odiitii, qindaa’ina fayyadamaa, ramaddii, sadarkaa nageenyaa, guyyaa/yeroo fudhatame. Waa’ee amala sirna tokkoo gaaffii ifa ta’e gaafadhu. Qabiyyee xalayaa dhuunfaa hin argu.',
    ],
];

if ($intent === 'stats') {
    $stats = getDashboardStats();
    $pending = (int) ($stats['pending_incoming'] ?? 0)
        + (int) ($stats['pending_outgoing'] ?? 0)
        + (int) ($stats['pending_internal'] ?? 0);
    $answer = sprintf(
        $answers['stats'][$language],
        (int) ($stats['incoming_count'] ?? 0),
        (int) ($stats['outgoing_count'] ?? 0),
        (int) ($stats['internal_count'] ?? 0),
        $pending
    );
} else {
    $answer = $answers[$intent][$language];
}

echo json_encode(['success' => true, 'answer' => $answer], JSON_UNESCAPED_UNICODE);
