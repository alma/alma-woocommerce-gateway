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
  eligibilities: Record<string, FeePlan>;
  gateway_name: string;
  is_in_page: boolean;
  label_button: string;
  nonce_value: string;
  title: string;
};

type AlmaBlocksProps = {
  settings: Settings;
  selectedFeePlan: string;
  setSelectedFeePlan: (value: string) => void;
};

export const AlmaBlocks: React.FC<AlmaBlocksProps> = ({
  settings,
  selectedFeePlan,
  setSelectedFeePlan,
}) => {
  const labels = {};
  let values = [];

  Object.keys(settings.eligibilities).forEach(function (key, index) {
    values.push(key);
    labels[key] = settings.eligibilities[key].installmentsCount + "x";
  });

  const handleClick = (optionKey) => {
    setSelectedFeePlan(optionKey);
  };

  const label = (
    <div className="toggleButtonFieldLabel">{settings.description}</div>
  );
  return (
    <>
      <IntlProvider locale="fr">
        <ToggleButtonsField
          className="toggleButtonField"
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
        <div className="alma-card-installments">
          <Installments feePlan={settings.eligibilities[selectedFeePlan]} />
        </div>
      </IntlProvider>
    </>
  );
};
