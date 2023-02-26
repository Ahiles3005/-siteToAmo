<?php
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NullTagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\BirthdayCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\DateTimeCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use Carbon\Carbon;
use League\OAuth2\Client\Token\AccessTokenInterface;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\EntitiesServices\Interfaces\HasParentEntity;
use AmoCRM\Filters\NotesFilter;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Collections\NotesCollection;
use AmoCRM\Models\Factories\NoteFactory;
use AmoCRM\Models\Interfaces\CallInterface;
use AmoCRM\Models\NoteType\CallInNote;
use AmoCRM\Models\NoteType\ServiceMessageNote;
use AmoCRM\Models\NoteType\SmsOutNote;
use Ramsey\Uuid\Uuid;


include_once __DIR__ . '/bootstrap.php';

$accessToken = getToken();
error_reporting(E_ALL);

$apiClient->setAccessToken($accessToken)
    ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
    ->onAccessTokenRefresh(
        function (AccessTokenInterface $accessToken, string $baseDomain) {
            saveToken(
                [
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $baseDomain,
                ],
            );
        },
    );


$_phone = ltrim($phone, '78');
$name = $_POST['name'];
$city = $_POST['city'] ?? '';
//поиск контакт
sleep(1);
$contactId = null;
$leadId = null;
try {
    $filter = new ContactsFilter();
    $filter->setQuery($_phone);
    $contact = $apiClient->contacts()->get($filter, ['leads'])->first();
    $contactId = $contact->getId();
    $ledData = $contact->getLeads() ?? null;
    if ($ledData !== null) {
        $leads = array_filter($ledData->toArray(), fn (array $data) => $data['closed_at'] === null);
        $leadId = $leads[0]['id'] ?? null;
    }
} catch (AmoCRMApiException $e) {
    // printError($e);
    // die;
}


sleep(3);
if ($contactId === null) {
    $contact = new ContactModel();
    $contact->setName($name);
    $customFields = new CustomFieldsValuesCollection();
    $phoneField = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
    //Установим значение поля
    $phoneField->setValues(
        (new MultitextCustomFieldValueCollection())
            ->add(
                (new MultitextCustomFieldValueModel())
                    ->setEnum('WORK')
                    ->setValue($phone),
            ),
    );
    $customFields->add($phoneField);
    $contact->setCustomFieldsValues($customFields);

    try {
        $contactId = $apiClient->contacts()->addOne($contact)->getId();
    } catch (AmoCRMApiException $e) {
        printError($e);
        //   die;
    }


}

//var_dump($contactId);

sleep(3);
$leadsService = $apiClient->leads();
if ($leadId === null) {
//Создадим сделку с заполненным бюджетом и привязанными контактами и компанией
    $lead = new LeadModel();
    $lead->setName('Заявка с сайта taxi-yandex-pro.ru')
        ->setStatusId(55453514)
        ->setContacts(
            (new ContactsCollection())
                ->add(
                    (new ContactModel())
                        ->setId($contactId),
                ),
        );

    $leadCustomFieldsValues = new CustomFieldsValuesCollection();
    $textCustomFieldValueModel = new TextCustomFieldValuesModel();
    $textCustomFieldValueModel->setFieldId(1293989);
    $textCustomFieldValueModel->setValues(
        (new TextCustomFieldValueCollection()) //_utm_content
        ->add((new TextCustomFieldValueModel())->setValue($_POST['_utm_content'] ?? '')),
    );
    $leadCustomFieldsValues->add($textCustomFieldValueModel);
    $lead->setCustomFieldsValues($leadCustomFieldsValues);


    $textCustomFieldValueModel->setFieldId(1293991);
    $textCustomFieldValueModel->setValues(
        (new TextCustomFieldValueCollection()) //_utm_medium
        ->add((new TextCustomFieldValueModel())->setValue($_POST['_utm_medium'] ?? '')),
    );
    $leadCustomFieldsValues->add($textCustomFieldValueModel);
    $lead->setCustomFieldsValues($leadCustomFieldsValues);

    $textCustomFieldValueModel->setFieldId(1293993);
    $textCustomFieldValueModel->setValues(
        (new TextCustomFieldValueCollection()) //_utm_campaign
        ->add((new TextCustomFieldValueModel())->setValue($_POST['_utm_campaign'] ?? '')),
    );
    $leadCustomFieldsValues->add($textCustomFieldValueModel);
    $lead->setCustomFieldsValues($leadCustomFieldsValues);

    $textCustomFieldValueModel->setFieldId(1293995);
    $textCustomFieldValueModel->setValues(
        (new TextCustomFieldValueCollection()) //_utm_source
        ->add((new TextCustomFieldValueModel())->setValue($_POST['_utm_source'] ?? '')),
    );
    $leadCustomFieldsValues->add($textCustomFieldValueModel);
    $lead->setCustomFieldsValues($leadCustomFieldsValues);

    $textCustomFieldValueModel->setFieldId(1293997);
    $textCustomFieldValueModel->setValues(
        (new TextCustomFieldValueCollection()) //_utm_term
        ->add((new TextCustomFieldValueModel())->setValue($_POST['_utm_term'] ?? '')),
    );
    $leadCustomFieldsValues->add($textCustomFieldValueModel);
    $lead->setCustomFieldsValues($leadCustomFieldsValues);

    $textCustomFieldValueModel->setFieldId(1294003);
    $textCustomFieldValueModel->setValues(
        (new TextCustomFieldValueCollection()) //referrer
        ->add((new TextCustomFieldValueModel())->setValue($_SESSION['referer'] ?? '')),
    );
    $leadCustomFieldsValues->add($textCustomFieldValueModel);
    $lead->setCustomFieldsValues($leadCustomFieldsValues);

    $textCustomFieldValueModel->setFieldId(1294017);
    $textCustomFieldValueModel->setValues(
        (new TextCustomFieldValueCollection()) // _ym_uid клиент id
        ->add((new TextCustomFieldValueModel())->setValue($_POST['_clid'])),
    );
    $leadCustomFieldsValues->add($textCustomFieldValueModel);
    $lead->setCustomFieldsValues($leadCustomFieldsValues);

    $textCustomFieldValueModel->setFieldId(1294019);
    $textCustomFieldValueModel->setValues(
        (new TextCustomFieldValueCollection()) //    _ym_counter счетчик яндекса
        ->add((new TextCustomFieldValueModel())->setValue('91630112')),
    );
    $leadCustomFieldsValues->add($textCustomFieldValueModel);
    $lead->setCustomFieldsValues($leadCustomFieldsValues);


    try {
        $leadsCollection = $leadsService->addOne($lead);
        $notesCollection = new NotesCollection();
        sleep(3);

//Создадим примечания
        $text = 'Город: ' . $city . PHP_EOL;
        $notesCollection = new NotesCollection();
        $serviceMessageNote = new ServiceMessageNote();
        $serviceMessageNote->setEntityId($leadsCollection->getId())
            ->setText($text)
            ->setService('Api Library')
            ->setCreatedBy(0);

        $notesCollection->add($serviceMessageNote);

        $leadNotesService = $apiClient->notes(EntityTypesInterface::LEADS);
        $notesCollection = $leadNotesService->add($notesCollection);


    } catch (AmoCRMApiException $e) {
//        printError($e);
        // die;
    }
} else {
    $notesCollection = new NotesCollection();
    $serviceMessageNote = new ServiceMessageNote();
    $serviceMessageNote->setEntityId($leadId)
        ->setText('Обратился еще раз')
        ->setService('Api Library')
        ->setCreatedBy(0);

    $notesCollection->add($serviceMessageNote);

    $leadNotesService = $apiClient->notes(EntityTypesInterface::LEADS);
    $notesCollection = $leadNotesService->add($notesCollection);
}

