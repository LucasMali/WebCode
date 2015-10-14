<?php
/**
 * Created by PhpStorm.
 * @author Lucas Maliszewski <lucas@gmail.com>
 * @since 6/11/15
 * Time: 9:05 AM
 *
 * Paypay IPN catcher.
 *
 * This landing page will simply grab any json response sent from Paypal and add it into a noSQL database MongoDB.
 *
 * The initial phase will setup a 200 http response code to let Paypal know we've accepted the transmission. Other wise,
 * if anything goes astray the system will set a fail http response code and email the tech team.
 *
 * @package paypalipn
 * @version 1
 */
http_response_code('200');

/**
 * Contains the default email to send issues out to.
 */
define('EMAIL', 'email@email.com');

/**
 * Contains the location of the class file(s)
 * @var $includeFiles multiType|string
 */
$includeFiles = ['PayPalIpn/PayPalIpn.php', 'Lib/MongoClientWrapper.php', 'Lib/ErrorHandler.php'];

/**
 * Contains the PayPalIpn logic.
 * @var $payPalIpn PayPalIpn
 */
$payPalIpn;

/**
 * Contains the log type
 * @var $type string;
 */
$type = 'notice';

/**
 * Contains the file name in question.
 * @var $file string
 */
$file = '';

/**
 * Contains the message to give to the log or the log and the email.
 * @var $message string
 */
$message = '';

/*
 * Run time start
 */
foreach ($includeFiles as $file) {
    if ((include_once $file) == false) {
        http_response_code('410');
        error_log('Could not find the class file: {$file}', 1, EMAIL);
        exit;
    } // end if failed to include file
} // end foreach

// Set the connection
try {
    $error = new Lib\ErrorHandler();

    // Only log if we are debugging
    if (PayPalIpn::DEBUG === true) {
        checkLogFile(PayPalIpn::LOGFILE);
        error_log('[' . date('r') . '] [notice] Evaluating log file passed' . PHP_EOL, 3, PayPalIpn::LOGFILE);
    }

    // Set the raw input to the Magic Global $_POST
    parse_str(file_get_contents('php://input'), $_POST);

    // See if post is populated.
    gotPost();

    // run the Paypal wrapper IPN process.
    $payPalIpn = new PayPalIpn(new \Lib\MongoClientWrapper());
    $payPalIpn->readPostData(file_get_contents('php://input'));
    $payPalIpn->crossCheckPaypal();
    $payPalIpn->verifyValidPurchase();
} catch (ErrorException $ee) {
    $type = 'error';
    $message = $ee->getMessage();
    $file = PayPalIpn::LOGFILE;
} catch (Exception $e) {
    $type = 'error';
    $message = $e->getMessage();
} finally {
    if (PayPalIpn::DEBUG === true) {
        error_log('[' . date('r') . '] [' . $type . '] ' . $message . PHP_EOL, 3, PayPalIpn::LOGFILE);
    }
    if ($type === 'error') {
        http_response_code('410');
        $error->mailError($message, $file);
        exit;
    }
} // end try

// Check the Magic Global $_POST for a null value
/**
 * Got Post
 *
 * Makes sure we have a populated POST
 *
 * @throws ErrorException
 */
function gotPost()
{
    if (empty($_POST)) {
        http_response_code('410');
        $message = 'No Post Parameters were found: ' . file_get_contents('php://input') . '<pre>' . print_r($_POST,
                true) . '</pre>';
        throw new ErrorException($message);
    } else {
        if (PayPalIpn::DEBUG === true) {
            error_log('[' . date('r') . '] [notice] Found POST Parameters' . PHP_EOL, 3, PayPalIpn::LOGFILE);
        }
    }
} // end got post

/**
 * @param $logfile
 *
 * @throws ErrorException
 */
function checkLogFile($logfile)
{
    // Check we have a file to write to
    if (!file_exists($logfile)) {
        // If not, try to create one.
        if (!touch($logfile)) {
            $error = 'Was unable to create the log file';
            throw new ErrorException($error);
        }
    } else {
        if ((int)substr(sprintf('%o', fileperms($logfile)), -3) < 644) {
            $error = 'Unable to write to the file with permissions set at: ' . substr(sprintf('%o',
                    fileperms($logfile)), -3);
            throw new ErrorException($error);
        }
    }
} // end checkLogFile