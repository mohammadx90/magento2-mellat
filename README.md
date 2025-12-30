# Mellat Bank Payment Gateway for Magento 2
# درگاه پرداخت بانک ملت برای مجنتو ۲
This module provides an integration between Mellat Bank (Behpardakht) and Magento 2. It is designed with advanced features to support modern Iranian e-commerce requirements.

Key Features:
-------------
- Robust Security: Server-side validation and secure redirection to the Behpardakht portal.

Compatibility:
-------------
- Magento Open Source / Adobe Commerce 2.3.x, 2.4.x
- PHP 7.4, 8.1, 8.2

Installation:
--------------
- Upload: Copy the content of the repository to app/code/Caferobot/Mellat.
- Enable: Run the following commands in your terminal:
  <code>
    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
    rm -rf pub/static/*
    php bin/magento setup:static-content:deploy  -f
  </code>
- Configure: Go to Admin > Stores > Configuration > Sales > Payment Methods to enter your Terminal ID, Username, and Password.



توضیحات فارسی
-----------
این ماژول ارتباط بین بانک ملت (به‌پرداخت) و فروشگاه‌ساز مجنتو ۲ را برقرار می‌کند. این ابزار با ویژگی‌های پیشرفته برای پاسخگویی به نیازهای تجارت الکترونیک ایران طراحی شده است.

ویژگی‌های کلیدی:
----------
- امنیت بالا: اعتبارسنجی سمت سرور و انتقال امن به درگاه به‌پرداخت.

سازگاری:
-----------
- مجنتو نسخه ۲.۳ و ۲.۴ (تمامی زیرنسخه‌ها)
- PHP 7.4, 8.1, 8.2

نحوه نصب:
-----------
- ۱. آپلود: محتوای مخزن را در مسیر app/code/Caferobot/Mellat کپی کنید.
- ۲. فعال‌سازی: دستورات زیر را در ترمینال خود اجرا کنید:
  <code>
    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
    rm -rf pub/static/*
    php bin/magento setup:static-content:deploy  -f
  </code>
- ۳. پیکربندی: به مسیر پنل مدیریت > فروشگاه > پیکربندی > فروش > روش‌های پرداخت رفته و مشخصات ترمینال (Terminal ID، نام کاربری و رمز عبور) را وارد نمایید.
