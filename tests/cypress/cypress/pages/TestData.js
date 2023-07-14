class TestData {

    // Content creation data.
    $content_title = "QA_Test_CypressSS"
    $content = "Lorem Ipsum Lorem Ipsum Lorem Ipsum Lorem Ipsum Lorem Ipsum"
    $first_name = "John"
    $last_name = "Doe"
    $company_name = "Acquia Inc."
    $adress_line_01 = "Cerebrum IT Park"
    $adress_line_02 = "Kalyani Nagar"
    $latitude = "18.5204"
    $longitude = "73.8567"
    $telephone_number = "9999999999"
    $place_type = "Office"
    $country = "India"
    $state = "Maharashtra"
    $city = "Pune"
    $postal_code = "411014"
    $start_date = "2025-01-04"
    $start_time = "10:00:00"
    $end_date = "2025-01-05"
    $end_time = "11:00:00"
    $door_date = "2025-01-03"
    $door_time = "10:00:00"
    $event_place = 'Brighton Office'
    $language = 'English'
    $content_author = 'Clare Harris'
    $content_type = 'Blog'
    $job_title = 'Assistance Coach'
    $person_type = 'Operations'
    $person_email = 'qa_cypress_test@acquia.com'
    default_hero_text = 'Medium length placeholder heading.'
    $publish_save_type = "Published"

    // Password policy.
    $policy1 = 'Minimum password character types: 3'
    $policy2 = 'Password character length of at least 8 characters'
    $policy3 = 'Password must not contain the user\'s username.'
    $policy_password = 'A1234567890b!'
    $policy_username = 'QA_User'
    $Deletion_msg_1_4_version = 'Account QA_User has been deleted.'

    // Taxonomy.
    $vocab_name = 'QA_Test_Vocab'
    $vocab_description = 'QA test vocabulary description'
    $term_name = 'QA_term'
    $term_description = 'QA test term description'

    // Extend.
    $starter_module = 'Acquia CMS Starter'
    $article_module = 'Acquia CMS Article'
    $audio_module = 'Acquia CMS Audio'
    $common_module = 'Acquia CMS Common'
    $development_module = 'Acquia CMS Development'
    $document_module = 'Acquia CMS Document'
    $event_module = 'Acquia CMS Event'
    $image_module = 'Acquia CMS Image'
    $page_module = 'Acquia CMS Page'
    $person_module = 'Acquia CMS Person'
    $place_module = 'Acquia CMS Place'
    $search_module = 'Acquia CMS Search'
    $starter_module = 'Acquia CMS Starter'
    $support_module = 'Acquia CMS Support'
    $toolbar_module = 'Acquia CMS Toolbar'
    $tour_module = 'Acquia CMS Tour'
    $video_module = 'Acquia CMS Video'

    // Name of the tabs.
    $content_tab = 'Content'
    $structure_tab = 'Structure'
    $site_studio_tab = 'Site Studio'
    $appearance_tab = 'Appearance'
    $extend_tab = 'Extend'
    $configuration_tab = 'Configuration'
    $people_tab = 'People'
    $reports_tab = 'Reports'
    $tour_tab = 'Acquia CMS Wizard'

    // Tour page content.
    $heading_tour_page = 'CMS Dashboard'
    $setupManuallyWizButton = 'body > div.ui-dialog.ui-corner-all.ui-widget.ui-widget-content.ui-front.acms-welcome-modal.ui-dialog-buttons > div.ui-dialog-buttonpane.ui-widget-content.ui-helper-clearfix > div > button.setup-manually.button.js-form-submit.form-submit.ui-button.ui-corner-all.ui-widget'

    // Get Started page content.
    $popup_title_description = 'Welcome to Acquia CMSWe\'ve created an easy step by step installation wizard to guide you through the necessary configurations\n'
    $heading_get_started ='CMS Dashboard'

    // Links.
    $baseUrl = Cypress.config('baseUrl') || Cypress.env('baseUrl')
    $baseURL = this.$baseUrl
    $published_url = this.$baseURL+'/qatestcypressss'
    $home_url = this.$baseURL+'/home-sitestudio'
    $article_url = this.$baseURL+'/articles'
    $events_url = this.$baseURL+'/events'
    $places_url = this.$baseURL+'/places'
    $people_url = this.$baseURL+'/people'
    $view_url = this.$baseURL+'/user/1'
    $scheduled_url = this.$baseURL+'/user/1/scheduled'
    $scheduled_media = this.$baseURL+'/user/1/scheduled_media'
    $edit_url = this.$baseURL+'/user/1/edit'
    $clone_url = this.$baseURL+'/entity_clone/user/1'
    $acquia_dam_url = this.$baseURL+'/user/1/acquia-dam'
    $moderation_dashboard_url = this.$baseURL+'/user/1/moderation-dashboard'
}
const testData = new TestData()
module.exports = testData
