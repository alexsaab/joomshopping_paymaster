**Настройка модуля оплаты с Joopshopping и PayMaster (с онлайн кассой)**

Тип подписи выставляем в настройках скрипта, такую же подпись делам и интерфейсе мерчанта Paymaster. НДС берется из продуктов: 18% или 10% или вообще без НДС.
В интерфейса мерчанта PayMaster выставляем:

Payment notification (POST): http://вашдомен/index.php?option=com_jshopping&controller=checkout&task=step7&act=notify&js_paymentclass=pm_paymaster&no_lang=1

Success redirect (POST):
http://вашдомен/index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=pm_paymaster

Failure redirect (POST):
http://вашдомен/index.php?option=com_jshopping&controller=checkout&task=step7&act=cancel&js_paymentclass=pm_paymaster

Разрешена замена URL: Да

Заливаем папку components в корень сайта.

Зайдите в административную панель Joomla CMS и выберите Компоненты — JoomShopping, пункт «Опции».

Зайдите в пункт меню Опции — Способы оплаты.

В списке способов оплаты — нажмите Создать.

В диалоге Добавить способ оплаты необходимо заполнить следующие поля: Публикация — Да Псевдоним: pm_paymaster Тип: Расширенный

Очень важно выставить правильную валюту по умолчанию рубли как на изображении, просто заменив EURO на RUB соблюдая все формальности. 


Версия для JoomShopping Version 4.11.3 (не ниже) и Joomla 3.0.3 (не ниже)



Если у вас возникли вопросы или замечания по модулю или вам необходимо его доработать, а также сделать любые доработки по Joomla CMS, обращайтесь:

•	Mail: awa77@mail.ru

•	Скайп: awa_77