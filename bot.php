<?php

$local = 'sql3.freesqldatabase.com';
$username = 'sql3760013';
$pass = 'EgkRMMJuqS';
$dbname = 'sql3760013';

// الاتصال بقاعدة البيانات
$conn = mysqli_connect($local, $username, $pass, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ضبط الترميز إلى UTF-8
mysqli_set_charset($conn, "utf8");

define('API_KEY','7030638109:AAEn3C-mi0CEvXuhGuAyiVAnjcgd6QFVvt8'); // استبدل API_KEY بمفتاح API الخاص بك

// وظيفة إرسال الطلبات إلى Telegram API
function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

// الحصول على التحديثات من Webhook
$update = json_decode(file_get_contents('php://input'));

if (isset($update->message)) {
    $message = $update->message;
    $chat_id = $message->chat->id; // ID الدردشة
    $text = mysqli_real_escape_string($conn, $message->text); // منع الأخطاء والاختراقات
    $username_telegram = $message->from->username; // اسم المستخدم في تليجرام
    $phone_number = isset($message->contact) ? $message->contact->phone_number : null; // رقم الهاتف إذا تم مشاركته

    // التحقق من الأمر المرسل
    if ($text == '/start') {
        // إرسال رسالة ترحيب تحتوي على اسم المستخدم أو رقم الهاتف فقط
        if ($username_telegram) {
            // إضافة @ قبل اسم المستخدم
            $reply = "🎉 *مرحباً بك في بوت مدارس الاحد، @$username_telegram!* 🎉\n\n🔹 *أرسل اسم المخدوم للحصول على التفاصيل.*\n🔹 *لأي استفسار، يرجى التواصل مع الدعم.* @AmirNady29"; 
        } elseif ($phone_number) {
            $reply = "🎉 *مرحباً بك في بوت مدارس الاحد،  هو: $phone_number* 🎉\n\n🔹 *أرسل اسم المخدوم للحصول على التفاصيل.*\n🔹 *لأي استفسار، يرجى التواصل مع الدعم.* @AmirNady29";
        } else {
            // إذا لم يكن هناك اسم مستخدم ولا رقم هاتف، تطلب رسالة لإنشاء اسم مستخدم
            $reply = "🎉 *مرحباً بك في بوت مدارس الاحد!* 🎉\n\n🔹 *يجب عليك تحديد اسم مستخدم خاص بك في تليجرام  لبدء التفاعل مع البوت.*\n🔹*لإنشاء اسم مستخدم في تليجرام، اتبع الخطوات التالية:*\n1️⃣ افتح تطبيق تليجرام.\n2️⃣ اضغط على القائمة في أعلى يسار الشاشة.\n3️⃣ اختر 'الإعدادات'.\n4️⃣ اختر 'اسم المستخدم' واضغط على 'تعيين اسم مستخدم'.\n5️⃣ اختر اسم مستخدم مميز يتضمن أرقام أو حروف فقط، ويجب ألا يتجاوز 32 حرفًا.";
        }

        // التحقق إذا كان اسم المستخدم غير فارغ
        if ($username_telegram) {
            // التحقق إذا كان المستخدم موجودًا في جدول المستخدمين
            $check_user_sql = "SELECT * FROM `userr` WHERE `user_telegram` = '$username_telegram'";
            $check_user_result = mysqli_query($conn, $check_user_sql);

            if (mysqli_num_rows($check_user_result) == 0) {
                // إذا لم يكن موجودًا، إضافة المستخدم الجديد فقط إذا كان اسم المستخدم غير فارغ
                $insert_user_sql = "INSERT INTO `userr` (`user_telegram`, `count_search`) VALUES ('$username_telegram', 1)";
                mysqli_query($conn, $insert_user_sql);
            }
        }
    } elseif (!$username_telegram) {
        // إذا لم يكن هناك اسم مستخدم، نرسل خطوات إنشاء اسم مستخدم
        $reply = "❌ *عذراً، يجب عليك تحديد اسم مستخدم في تليجرام للبحث.*\n🔹 *يرجى تحديد اسم مستخدم لتتمكن من البحث.*\n\n💡 *لإنشاء اسم مستخدم في تليجرام، اتبع الخطوات التالية:*\n1️⃣ افتح تطبيق تليجرام.\n2️⃣ اضغط على القائمة في أعلى يسار الشاشة.\n3️⃣ اختر 'الإعدادات'.\n4️⃣ اختر 'اسم المستخدم' واضغط على 'تعيين اسم مستخدم'.\n5️⃣ اختر اسم مستخدم مميز يتضمن أرقام أو حروف فقط، ويجب ألا يتجاوز 32 حرفًا.";
    } else {
        // البحث في قاعدة البيانات باستخدام mysqli_query
        $sql = "SELECT * FROM students,class,`years`,`service` 
                WHERE students.cid = class.cid 
                AND years.yid = class.yid 
                AND years.seid = service.seid 
                AND students.stname LIKE '%$text%'";
        $result = mysqli_query($conn, $sql);

        // التحقق من النتائج
        if (mysqli_num_rows($result) > 0) {
            $reply = "🔍 *تم العثور على النتائج التالية:*\n\n";
            while ($row = mysqli_fetch_array($result)) {
                $reply .= "\n";
                $reply .= "👤 *الاسم*: `" . $row['stname'] . "`\n";
                $reply .= "📚 *الفصل*: `" . $row['cname'] . "`\n";
                $reply .= "📅 *السنة*: `" . $row['yname'] . "`\n";
                $reply .= "🙏 *الخدمة*: `" . $row['sename'] . "`\n";
            }
            
            // تحديث عدد مرات البحث في قاعدة البيانات (زيادة العد 1)
            $update_search_count_sql = "UPDATE `userr` SET `count_search` = `count_search` + 1 WHERE `user_telegram` = '$username_telegram'";
            mysqli_query($conn, $update_search_count_sql);
        } else {
            $reply = "❌ *عذرًا، لم يتم العثور على نتائج تطابق بحثك.*";
            
            // حتى إذا كانت النتائج خطأ، نزيد العدد في `count_search`
            $update_search_count_sql = "UPDATE `userr` SET `count_search` = `count_search` + 1 WHERE `user_telegram` = '$username_telegram'";
            mysqli_query($conn, $update_search_count_sql);
        }
    }

    // إرسال الرد إلى Telegram مع دعم Markdown
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $reply,
        'parse_mode' => 'Markdown', // لتفعيل التنسيق
    ]);
}

// إغلاق الاتصال بقاعدة البيانات
mysqli_close($conn);

?>
