/**
 * Checkout blocks component.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

import "@alma/react-components/style.css";
import "@alma/react-components/global.css";
import "../../css/alma-checkout-blocks.css";
import { ToggleButtonsField } from "@alma/react-components";
import React from "react";
import { Installments } from "./Installments/Installments";
import { IntlProvider } from "react-intl";
import classNames from "classnames";

export type PaymentPlan = {
  customer_fee: number;
  due_date: number;
  purchase_amount: number;
  total_amount: number;
  localized_due_date: string;
};

export type FeePlan = {
  annualInterestRate?: null | number;
  constraints?: null | object;
  customerTotalCostAmount?: number;
  customerTotalCostBps?: number;
  deferredDays?: number;
  deferredMonths?: number;
  installmentsCount: number;
  isEligible: boolean;
  paymentPlan: PaymentPlan[];
  reasons: null | string;
};

type Settings = {
  default_plan: string[];
  description: string;
  plans: Record<string, FeePlan>;
  gateway_name: string;
  is_in_page: boolean;
  label_button: string;
  nonce_value: string;
  title: string;
  amount_in_cents: number;
};

type AlmaBlocksProps = {
  settings: Settings;
  selectedFeePlan: string;
  setSelectedFeePlan: (value: string) => void;
  hasInPage: boolean;
  totalPrice: number
};

export const AlmaBlocks: React.FC<AlmaBlocksProps> = ({
  settings,
  selectedFeePlan,
  setSelectedFeePlan,
  hasInPage,
  totalPrice
}) => {
  const labels = {};
  let values = [];

  Object.keys(settings.plans).forEach(function (key, index) {
    values.push(key);
    if (
      settings.gateway_name === "alma_pay_later" ||
      settings.gateway_name === "alma_in_page_pay_later"
    ) {
      if (settings.plans[key].deferredDays > 0) {
        labels[key] = "D+" + settings.plans[key].deferredDays;
      } else if (settings.plans[key].deferredMonths > 0) {
        labels[key] = "M+" + settings.plans[key].deferredMonths;
      }
    } else {
      labels[key] = settings.plans[key].installmentsCount + "x";
    }
  });

  const handleClick = (optionKey) => {
    setSelectedFeePlan(optionKey);
  };

  const isPayNow = settings.gateway_name === "alma_pay_now";

  const label = (
    <div className="toggleButtonFieldLabel">{settings.description}</div>
  );
  return (
      <IntlProvider locale="fr">
        {isPayNow && <div className={"payNowLabel"}>{label}</div>}
        <div className={"buttonsContainer"}>
          <div className={classNames({ payNow: isPayNow })}>
            <ToggleButtonsField
            className={"toggleButtonField"}
            options={values}
            optionLabel={(key) => labels[key]}
            optionKey={(key) => key}
            onChange={(key) => handleClick(key)}
            value={selectedFeePlan}
            label={label}
            wide={false}
            size={"sm"}
            error=""
            />
          </div>
        </div>
        {!hasInPage && (
          <div className="alma-card-installments">
            <Installments
              feePlan={settings.plans[selectedFeePlan]}
              amountInCents={totalPrice}
            />
          </div>
        )}
      </IntlProvider>
  );
};
