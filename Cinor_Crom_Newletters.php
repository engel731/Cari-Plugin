<?php 

use Entity\Mail;

use Model\MailManager;
use Model\StreetManager;

class Cinor_Crom_Newletters
{
    private $_mailManager;
    private $_streetManager;
    
    public function __construct() 
    {
        global $wpdb;
        $this->_mailManager = new MailManager($wpdb);
        $this->_streetManager = new StreetManager($wpdb);

        add_action('admin_init', array($this, 'register_settings'));
        add_filter('cinor_crom_shortcode_response', array($this, 'save_mail'));
    }

    public function register_settings()
    {
        register_setting('cinor_crom_newsletter_settings', 'cinor_crom_newsletter_sender');
        register_setting('cinor_crom_newsletter_settings', 'cinor_crom_newsletter_object');
        register_setting('cinor_crom_newsletter_settings', 'cinor_crom_newsletter_content');
        
        add_settings_section('cinor_crom_newsletter_section', 'Paramètres d\'envoi', array($this, 'section_html'), 'cinor_crom_newsletter_settings');
        
        add_settings_field('cinor_crom_newsletter_sender',  'Expéditeur', array($this, 'sender_html'),  'cinor_crom_newsletter_settings', 'cinor_crom_newsletter_section');
        add_settings_field('cinor_crom_newsletter_object',  'Objet',      array($this, 'object_html'),  'cinor_crom_newsletter_settings', 'cinor_crom_newsletter_section');
        add_settings_field('cinor_crom_newsletter_content', 'Contenu',    array($this, 'content_html'), 'cinor_crom_newsletter_settings', 'cinor_crom_newsletter_section');
    }

    public function save_mail($msg) 
    {
        if(isset($_POST['mail'], $_POST['id-lieu'])) {
            if($this->_streetManager->idDoesExist($_POST['id-lieu'])) {
                $mail = new Mail([
                    'mail' => $_POST['mail'],
                    'lieu' => $_POST['id-lieu']
                ]);
                
                if($mail->isValid()) {
                    if($this->_mailManager->notDoubleLocation($mail)) {
                        $this->_mailManager->add($mail);
                        $response = 'L\'email à bien été ajouté'; 
                    } else {
                        $response = 'L\'email est déjà présente pour ce lieu'; 
                    }
                } else {
                    $erreur = $mail->erreurs()[0];
        
                    if($erreur == Mail::MAIL_INVALIDE) {
                        $response = 'Le format d\'email n\'est pas valide'; 
                    }
                }
            } else {
                $response = 'La rue n\'existe pas'; 
            }

            return $msg = $response;
        }
    }

    public function send_newsletter()
    {
        $mails = $this->_mailManager->getMailToSend($wpdb);
        $sender = get_option('cinor_crom_newsletter_sender', 'tanguy731.freelance@gmail.com');
        $header = array('From: '.$sender);
       
        foreach($mails as $mail) {
            $object = get_option('cinor_crom_newsletter_object', 'Ramassage pour @type_dechet');
            $content = get_option('cinor_crom_newsletter_content', 'Pensez à deposer @type_dechet à l\'endroit habituel');

            switch ($mail['dechet']) {
                case "Bac gris":
                    $dechet = "le bac gris";
                    break;
                case "Bac Jaune":
                    $dechet = "le bac jaune";
                    break;
                case "Emcombrants":
                    $dechet = "les emcombrants";
                    break;
                case "Déchets verts":
                    $dechet = "les déchets verts";
                    break;
            }

            //@type_dechet
            $object = preg_replace("#@type_dechet#", $dechet, $object);
            $content = preg_replace("#@type_dechet#", $dechet, $content);

            //@date_passage
            $object = preg_replace("#@date_passage#", $dechet, $mail['date_passage']);
            $content = preg_replace("#@date_passage#", $dechet, $mail['date_passage']);
            
            wp_mail($mail['mail'], $object, $content, $header);
        }
    }
    
    public function section_html()
    {
        echo 'Renseignez les paramètres d\'envoi de la newsletter ↓';
    }

    public function sender_html()
    {
        ?><input 
            class="form-control"
            type="email" 
            name="cinor_crom_newsletter_sender" 
            value="<?php echo get_option('cinor_crom_newsletter_sender')?>"
        /><?php
    }

    public function object_html()
    {
        ?><input 
            class="form-control"
            type="text" 
            name="cinor_crom_newsletter_object" 
            value="<?php echo get_option('cinor_crom_newsletter_object')?>"
        /><?php
    }

    public function content_html()
    {
        ?><textarea 
            class="form-control" 
            style="height: 100px;" 
            name="cinor_crom_newsletter_content"><?php echo get_option('cinor_crom_newsletter_content')?></textarea><?php
    }

    public function install() { $this->_mailManager->create(); }
    public function uninstall() { $this->_mailManager->delete(); }
}