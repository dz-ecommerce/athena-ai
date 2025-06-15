<!-- Unternehmensprofil -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Unternehmensprofil', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php 
        athena_ai_floating_input([
            'name' => 'athena_ai_profiles[company_name]',
            'id' => 'company_name',
            'label' => 'Firmenname',
            'value' => $profile_data['company_name'] ?? '',
            'type' => 'text'
        ]); 
        ?>
        <?php 
        // Branchendaten definieren
        $industry_groups = [
            'Dienstleistungen' => [
                'accounting' => 'Buchhaltung & Steuern',
                'advertising' => 'Werbung & Marketing',
                'consulting' => 'Unternehmensberatung',
                'financial' => 'Finanzdienstleistungen',
                'insurance' => 'Versicherungen',
                'legal' => 'Rechtsberatung',
                'real_estate' => 'Immobilien'
            ],
            'IT & Technologie' => [
                'it_services' => 'IT-Dienstleistungen',
                'software' => 'Softwareentwicklung',
                'web_design' => 'Webdesign & -entwicklung',
                'ecommerce' => 'E-Commerce',
                'telecommunications' => 'Telekommunikation',
                'data_analytics' => 'Datenanalyse',
                'cloud_computing' => 'Cloud Computing',
                'cybersecurity' => 'IT-Sicherheit'
            ],
            'Handel & Einzelhandel' => [
                'retail' => 'Einzelhandel',
                'wholesale' => 'Großhandel',
                'ecommerce_retail' => 'Online-Handel',
                'consumer_goods' => 'Konsumgüter',
                'food_retail' => 'Lebensmittelhandel',
                'fashion' => 'Mode & Bekleidung',
                'electronics_retail' => 'Elektronik',
                'furniture' => 'Möbel & Einrichtung'
            ],
            'Produktion & Fertigung' => [
                'manufacturing' => 'Fertigungsindustrie',
                'automotive' => 'Automobilindustrie',
                'aerospace' => 'Luft- und Raumfahrt',
                'electronics' => 'Elektronik & Elektrotechnik',
                'chemicals' => 'Chemische Industrie',
                'pharma' => 'Pharmazeutische Industrie',
                'machinery' => 'Maschinenbau',
                'textiles' => 'Textilindustrie'
            ],
            'Gesundheitswesen' => [
                'healthcare' => 'Gesundheitswesen',
                'medical_practice' => 'Arztpraxis',
                'hospital' => 'Krankenhaus',
                'biotech' => 'Biotechnologie',
                'medical_devices' => 'Medizintechnik',
                'pharmaceutical' => 'Pharmaindustrie',
                'healthcare_services' => 'Gesundheitsdienstleistungen',
                'eldercare' => 'Altenpflege'
            ],
            'Bildung & Forschung' => [
                'education' => 'Bildungseinrichtungen',
                'school' => 'Schulen',
                'university' => 'Hochschulen & Universitäten',
                'vocational_training' => 'Berufsbildung',
                'research' => 'Forschungseinrichtungen',
                'e_learning' => 'E-Learning & Online-Bildung'
            ],
            'Weitere Branchen' => [
                'agriculture' => 'Landwirtschaft',
                'architecture' => 'Architektur & Ingenieurwesen',
                'art' => 'Kunst & Design',
                'beauty' => 'Schönheit & Kosmetik',
                'construction' => 'Bauwesen',
                'energy' => 'Energie & Versorgung',
                'entertainment' => 'Unterhaltung & Freizeit',
                'food' => 'Gastronomie & Lebensmittel',
                'hospitality' => 'Hotellerie & Gastgewerbe',
                'media' => 'Medien & Kommunikation',
                'transport' => 'Transport & Logistik',
                'travel' => 'Tourismus & Reisen',
                'other' => 'Sonstige'
            ]
        ];
        
        athena_ai_floating_select([
            'name' => 'athena_ai_profiles[company_industry]',
            'id' => 'company_industry',
            'label' => 'Branche',
            'value' => $profile_data['company_industry'] ?? '',
            'options' => $industry_groups,
            'placeholder' => 'Branche auswählen'
        ]); 
        ?>

        <div class="md:col-span-2">
            <div class="flex justify-end mb-2">
                <button type="button" id="athena-ai-assistant-btn" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded shadow-sm transition-colors">
                    <i class="fas fa-magic mr-2"></i>
                    Athena AI Assistent
                </button>
            </div>
            <?php 
            athena_ai_floating_textarea([
                'name' => 'athena_ai_profiles[company_description]',
                'id' => 'company_description',
                'label' => 'Kurzbeschreibung des Unternehmens',
                'value' => $profile_data['company_description'] ?? '',
                'rows' => 5,
                'help_text' => 'Maximal 200 Wörter'
            ]); 
            ?>
        </div>
    </div>
</fieldset> 