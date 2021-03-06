<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Yves\Braintree\Form;

use Generated\Shared\Transfer\BraintreePaymentTransfer;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Kernel\Store;
use SprykerEco\Shared\Braintree\BraintreeConfig;
use SprykerEco\Shared\Braintree\BraintreeConstants;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PayPalSubForm extends AbstractSubForm
{
    public const PAYMENT_METHOD = 'pay-pal';

    public const ENV = 'env';
    public const CLIENT_TOKEN = 'clientToken';
    public const AMOUNT = 'amount';
    public const CURRENCY = 'currency';
    public const LOCALE = 'locale';

    /**
     * @return string
     */
    public function getName()
    {
        return BraintreeConfig::PAYMENT_METHOD_PAY_PAL;
    }

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return BraintreeConfig::PAYMENT_METHOD_PAY_PAL;
    }

    /**
     * @return string
     */
    public function getTemplatePath()
    {
        return BraintreeConfig::PROVIDER_NAME . '/' . static::PAYMENT_METHOD;
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BraintreePaymentTransfer::class,
        ])->setRequired(static::OPTIONS_FIELD_NAME);
    }

    /**
     * @param \Symfony\Component\Form\FormView $view The view
     * @param \Symfony\Component\Form\FormInterface $form The form
     * @param array $options The options
     *
     * @return void
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        /** @var \Generated\Shared\Transfer\QuoteTransfer $quote */
        $quote = $form->getParent()->getViewData();

        $view->vars[static::ENV] = Config::get(BraintreeConstants::ENVIRONMENT);
        $view->vars[static::CLIENT_TOKEN] = $this->generateClientToken();
        $view->vars[static::AMOUNT] = $quote->getTotals()->getGrandTotal();
        $view->vars[static::CURRENCY] = $quote->getCurrency()->getCode();
        $view->vars[static::LOCALE] = Store::getInstance()->getCurrentLocale();
    }
}
