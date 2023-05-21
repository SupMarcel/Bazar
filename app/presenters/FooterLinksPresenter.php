<?php

namespace App\Presenters;

class FooterLinksPresenter extends BasePresenter
{
    public function renderFocusBazaar()
    {
        $this->template->heading = $this->translator->translate('messages.FooterLinks.focusBazaar.heading');
        $this->template->content = $this->translator->translate('messages.FooterLinks.focusBazaar.content');
    }

    public function renderProhibitedItems()
    {
        $this->template->heading = $this->translator->translate('messages.FooterLinks.prohibitedItems.heading');
        $this->template->listItems = $this->translator->translate('messages.FooterLinks.prohibitedItems.listItems');
        $this->template->content = $this->translator->translate('messages.FooterLinks.prohibitedItems.content');
    }

    public function renderHowToBuy()
    {
        $this->template->heading = $this->translator->translate('messages.FooterLinks.howToBuy.heading');
        $this->template->content = $this->translator->translate('messages.FooterLinks.howToBuy.content');
    }

    public function renderHowToSell()
    {
        $this->template->heading = $this->translator->translate('messages.FooterLinks.howToSell.heading');
        $this->template->content = $this->translator->translate('messages.FooterLinks.howToSell.content');
    }
    
    public function renderTermsAndConditions()
    {
        $this->template->header = $this->translator->translate('messages.FooterLinks.termsAndConditions.header');
        $this->template->companyInfo = $this->translator->translate('messages.FooterLinks.termsAndConditions.companyInfo');
        $this->template->mediatorInfo = $this->translator->translate('messages.FooterLinks.termsAndConditions.mediatorInfo');
        $this->template->disclaimer = $this->translator->translate('messages.FooterLinks.termsAndConditions.disclaimer');
        $this->template->buyerSellerProtection = $this->translator->translate('messages.FooterLinks.termsAndConditions.buyerSellerProtection');
        $this->template->registrationRequirements = $this->translator->translate('messages.FooterLinks.termsAndConditions.registrationRequirements');
        $this->template->freeUsage = $this->translator->translate('messages.FooterLinks.termsAndConditions.freeUsage');
    }

    public function renderContact()
    {
        $this->template->header = $this->translator->translate('FooterLinks.contact.header');
        $this->template->emailPhone = $this->translator->translate('FooterLinks.contact.emailPhone');
    }
}