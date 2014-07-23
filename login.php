<?php
/**
 * @package      CrowdFunding
 * @subpackage   Plugins
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2014 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;

/**
 * CrowdFunding Login Plug-in displays a login form on step 2 of the payment wizard.
 *
 * @package      CrowdFunding
 * @subpackage   Plugins
 */
class plgCrowdFundingLogin extends JPlugin
{
    protected $autoloadLanguage = true;

    /**
     * @var JApplicationSite
     */
    protected $app;

    protected $loginForm;
    protected $returnUrl;

    protected $rewardId;
    protected $amount;

    /**
     * This method prepares a payment gateway - buttons, forms,...
     * That gateway will be displayed on the summary page as a payment option.
     *
     * @param string    $context This string gives information about that where it has been executed the trigger.
     * @param object    $item    A project data.
     * @param Joomla\Registry\Registry $params  The parameters of the component
     *
     * @return null|string
     */
    public function onPaymentDisplay($context, &$item, &$params)
    {
        if (strcmp("com_crowdfunding.payment.step2", $context) != 0) {
            return null;
        }

        if ($this->app->isAdmin()) {
            return null;
        }

        $doc = JFactory::getDocument();
        /**  @var $doc JDocumentHtml */

        // Check document type
        $docType = $doc->getType();
        if (strcmp("html", $docType) != 0) {
            return null;
        }

        // Get user ID.
        $userId  = JFactory::getUser()->get("id");

        // Display login form
        if (!$userId) {

            // Get the form.
            JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
            JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

            $form = JForm::getInstance('com_users.login', 'login', array('load_data' => false), false, false);

            $this->loginForm = $form;

            $this->returnUrl = CrowdFundingHelperRoute::getBackingRoute($item->slug, $item->catslug);

            // Get the path for the layout file
            $path = JPluginHelper::getLayoutPath('crowdfunding', 'login');

            // Render the login form.
            ob_start();
            include $path;
            $html = ob_get_clean();

        } else { // Redirect to step "Payment".

            // Get the payment process object and
            // store the selected data from the user.
            $paymentProcessContext    = CrowdFundingConstants::PAYMENT_PROCESS_CONTEXT . $item->id;
            $paymentProcess           = $this->app->getUserState($paymentProcessContext);

            $this->rewardId = $paymentProcess->rewardId;
            $this->amount   = $paymentProcess->amount;

            // Get the path for the layout file
            $path = JPluginHelper::getLayoutPath('crowdfunding', 'login', 'redirect');

            // Render the login form.
            ob_start();
            include $path;
            $html = ob_get_clean();

            // Include JavaScript code to redirect user to next step.

            $filter    = JFilterInput::getInstance();

            $processUrl = $filter->clean(
                JUri::base()."index.php?option=com_crowdfunding&task=backing.process&id=".(int)$item->id."&rid=".(int)$this->rewardId."&amount=".rawurldecode($this->amount)."&".JSession::getFormToken(). "=1"
            );

            $js = '
jQuery(document).ready(function() {
     window.location.replace("'.$processUrl.'");
});
            ';

            $doc->addScriptDeclaration($js);
        }

        return $html;
    }

    /**
     * This method is used from the system to authorize step 2,
     * when you use a payment wizard in four steps.
     * If this method return true, the system will continue to step 2.
     *
     * @param string    $context This string gives information about that where it has been executed the trigger.
     * @param object $item
     * @param Joomla\Registry\Registry $params
     * @param JUser $user
     *
     * @return bool
     */
    public function onPaymentAuthorize($context, &$item, &$params, &$user)
    {
        if (strcmp("com_crowdfunding.payment.authorize", $context) != 0) {
            return null;
        }

        if ($this->app->isAdmin()) {
            return null;
        }

        $doc = JFactory::getDocument();
        /**  @var $doc JDocumentHtml */

        // Check document type
        $docType = $doc->getType();
        if (strcmp("html", $docType) != 0) {
            return null;
        }

        return true;
    }
}