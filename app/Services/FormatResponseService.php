<?php

namespace App\Services;

use App\Models\Credential;
use Illuminate\Support\Facades\Http;

class FormatResponseService
{
    public $keap_api_key;

    public function __construct()
    {
        $credentials = Credential::first();
        $this->keap_api_key = $credentials->keap_api_key;
    }

    public function formatResponse($email)
    {
        // full field definitions (ID => field_name)
        $allCustomFields = [
            2 => 'PropertyType',
            8 => 'NumberofBedrooms',
            10 => 'Garage',
            12 => 'GarageLocation',
            14 => 'AnyExtensions',
            16 => 'PrivateGarden',
            18 => 'VacantorOccupied',
            20 => 'PropertyLink',
            22 => 'SpecificConcerns',
            24 => 'AgentCompanyName',
            26 => 'AgentName',
            28 => 'AgentPhoneNumber',
            123 => 'ComplaintDetails',
            165 => 'AgentsEmail',
            175 => 'AnticipatedExchangeDate',
            191 => 'FullAddress',
            193 => 'MarketValue',
            195 => 'HouseorFlat',
            197 => 'NumberofBedrooms1',
            203 => 'Listedbuilding',
            207 => 'FormFields',
            208 => 'BreakdownOfEstimatedRepairCosts',
            210 => 'AerialRoofAndChimney',
            212 => 'InsuranceReinstatementValuation',
            214 => 'ThermalImages',
            218 => 'Level1URL',
            220 => 'Level1Price',
            222 => 'Level2URL',
            224 => 'Level2Price',
            226 => 'Level3URL',
            228 => 'Level3Price',
            234 => 'RedirectURL',
            238 => 'Level4Price',
            240 => 'Level4URL',
            242 => 'BOOKASURVEY',
            474 => 'SURVEYDATEAGREED',
            477 => 'KPFLINK',
            501 => 'KPFRIGHTMOVEZOOPLALINK',
            513 => 'BLOGTOPICFORM',
            515 => 'WelcomeVideo',
            525 => 'SUMMARYREPORTVIDEOLINK',
            529 => 'ClientConcerns',
            531 => 'PropertyEra',
            551 => 'CHARACTERISTICSAREA',
            553 => 'SURVEYORSNOTES2',
            555 => 'THINGSYOURSURVEYORSHOULDCHECKFOR',
            557 => 'PROPERTYAGEANDCHARACTERISTICS1',
            559 => 'COMMONDEFECTS1',
            561 => 'RISKOFINHERENTDEFECT',
            563 => 'RECOMMENDEDSURVEYTYPE1',
            565 => 'LOCALAREAANDENVIRONMENTAL',
            567 => 'DESKTOPOBSERVATIONS',
            569 => 'SURVEYTRANSRIPTION',
            575 => 'THEFINALFULLREPORTOUTPUTINHTML',
            577 => 'SUMMARYREPORTTRANSCRIPTION',
            579 => 'SolicitorFirmName',
            581 => 'ConveyancerName',
            585 => 'SolicitorPhoneNumber1',
            589 => 'SolicitorAddress',
            591 => 'ExchangeDate',
            597 => 'WetransferLink',
            599 => 'SpriftLink',
            601 => 'QUOTESUMMARYPAGE',
            603 => 'sqftarea',
            605 => 'SolicitorsEmail',
            621 => 'infcustomSignature',
            625 => 'pdflink1',
            629 => 'selectedlevel',
            633 => 'level3addon1',
            635 => 'level3addon2',
            637 => 'level3addon3',
            639 => 'GardenLocation',
            641 => 'Garden',
            647 => 'StripePaymentLink',
        ];

        $url = 'https://api.infusionsoft.com/crm/rest/v1/contacts?email=' . urlencode($email) . '&optional_properties=custom_fields';

        $response = Http::withHeaders([
            'X-Keap-API-Key' => $this->keap_api_key,
            'Authorization' => 'Bearer ' . $this->keap_api_key,
            'Content-Type' => 'application/json',
        ])->get($url);

        $data = $response->json();
        if (empty($data['contacts'])) {
            return ['error' => 'No contact found'];
        }

        $contact = $data['contacts'][0];

        $formatted = [
            'id' => $contact['id'] ?? null,
            'first_name' => $contact['given_name'] ?? null,
            'last_name' => $contact['family_name'] ?? null,
            'phone_number' => $contact['phone_numbers'][0]['number'] ?? null,
            'email' => $contact['email_addresses'][0]['email'] ?? null,
        ];

        foreach ($contact['custom_fields'] as $field) {
            $id = $field['id'];
            $content = $field['content'];
            $fieldName = $allCustomFields[$id] ?? "custom_{$id}";
            $formatted[$fieldName] = $content;
        }

        return $formatted;
    }
}
