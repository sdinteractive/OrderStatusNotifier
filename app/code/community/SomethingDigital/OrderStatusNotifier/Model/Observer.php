<?php

class SomethingDigital_OrderStatusNotifier_Model_Observer extends Mage_Core_Model_Abstract
{
    public function notifyStatus()
    {
        try {
            if (Mage::getStoreConfigFlag('sales/sd_ordernotifier/enabled')) {
                $statusCheck = explode(',', Mage::getStoreConfig('sales/sd_ordernotifier/order_status'));
                $startPoint = Mage::getStoreConfig('sales/sd_ordernotifier/start_point');
                $waitPeriod = Mage::getStoreConfig('sales/sd_ordernotifier/wait_period');
                $orders = Mage::getModel('sales/order')->getCollection()->join(
                            array('payment' => 'sales/order_payment'),
                            'main_table.entity_id=payment.parent_id',
                            array('payment_method' => 'payment.method')
                        );

                $orders->addFieldToFilter('status', array('in' => $statusCheck))
                        ->addFieldToFilter('created_at', array('gt' => date("Y-m-d H:i:s", strtotime('-' . $startPoint))))
                        ->addFieldToFilter('created_at', array('lteq' => date("Y-m-d H:i:s", strtotime('-'. $waitPeriod))));
                
                $orderList = "<p>Orders older than " . $waitPeriod . "</p>\n";
                foreach ($orders as $order) {
                    $orderList .= "<p>Order ID: " . $order->getIncrementId() .  "\t";
                    $orderList .= "Order Status: " . $order->getStatus() . "\t";
                    $orderList .= "Payment Method: " . $order->getPaymentMethod() . "</p>\n";
                }
                $emailTemplate  = Mage::getModel('core/email_template')->loadDefault('sd_ordernotifier_email_template');
                $emailVars = array('orderlist' => $orderList, 'date' => date("Y-m-d"));
                $emails = explode(",", Mage::getStoreConfig('sales/sd_ordernotifier/emails'));
                $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
                $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name'));
                $emailTemplate->setTemplateSubject("Order Status Notification");
                $emailTemplate->send($emails, "Team", $emailVars);
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Mage::log($errorMessage, null, "order-status-error.log");
        }
    }
}
