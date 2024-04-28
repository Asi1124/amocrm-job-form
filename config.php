<?php

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Client\LongLivedAccessToken;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;

//подключение библиотеки
include_once 'vendor/autoload.php';

//Подключение долгосрочного токена, один из трех вариантов подключения, два других https://github.com/amocrm/amocrm-api-php/tree/master?tab=readme-ov-file#подход-к-работе-с-библиотекой
$apiClient = new AmoCRMApiClient();

$longLivedAccessToken = new LongLivedAccessToken('example');

$apiClient->setAccessToken($longLivedAccessToken)
    ->setAccountBaseDomain('example.amocrm.ru');


//создание контакта и его полей, сделал отдельно от lead, тк получилось много кода,
// возникло желание разделить логику lead и contact, для понимания, как сократить-не придумал.
$contact = new ContactModel();
// стразу ставим имя, это самое простое
$contact->setName($_POST['name']);

// создаем коллекцию для полей
$ContactCustomFieldsValues = new CustomFieldsValuesCollection();

//поле с номером телефона
$MultitextCustomFieldValuesModelTel =
    (new MultitextCustomFieldValuesModel())
        ->setFieldId('222477')
        ->setValues(
            (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                        ->setEnum('WORKDD')
                        ->setValue($_POST['tel'])
         )
);
//поле с email
$MultitextCustomFieldValuesModel =
    (new MultitextCustomFieldValuesModel())->setFieldId('222479')
        ->setValues(
             (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                     ->setEnum('WORK')
                     ->setValue($_POST['email'])
        )
);
//передаем значения в contact и сохраняем
$ContactCustomFieldsValues->add($MultitextCustomFieldValuesModel);
$ContactCustomFieldsValues->add($MultitextCustomFieldValuesModelTel);
$contact->setCustomFieldsValues($ContactCustomFieldsValues);

try {
    $contactModel = $apiClient->contacts()->addOne($contact);
} catch (AmoCRMApiException $e) {
    PrintError($e);
    die;
}


// создаем lead/сделку
$leadsService = $apiClient->leads();

$lead = new LeadModel();

// устанавливаем имя, стоимость, и передаем $contact
$lead->setName('Название сделки')
    ->setPrice($_POST['price'])
    ->setContacts(
        (new ContactsCollection())
        ->add($contact)
    );

//кастомное поле с проведенным временем на сайте больше/меньше 30с
$leadCustomFieldsValues = new CustomFieldsValuesCollection();
$textCustomFieldValueModel = new TextCustomFieldValuesModel();
$textCustomFieldValueModel->setFieldId(248249);
$textCustomFieldValueModel->setValues(
    (new TextCustomFieldValueCollection())
        ->add((new TextCustomFieldValueModel())->setValue($_POST['time_spent']))
);

$leadCustomFieldsValues->add($textCustomFieldValueModel);
$lead->setCustomFieldsValues($leadCustomFieldsValues);


$leadsCollection = new LeadsCollection();
$leadsCollection->add($lead);

try {
    $leadsCollection = $leadsService->add($leadsCollection);
} catch (AmoCRMApiException $e) {
   PrintError($e);
    die;
}

//return redirect