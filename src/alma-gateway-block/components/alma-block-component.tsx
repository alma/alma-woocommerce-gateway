/**
 * Gateway Block Component.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

import "@alma/react-components/style.css";
import "../alma-gateway-block.css";
import {ToggleButtonsField} from "@alma/react-components";
import * as React from "react";
import {Installments} from "./Installments/Installments";
import {IntlProvider} from "react-intl";
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

type GatewaySettings = {
    default_plan: string[];
    description: string;
    plans: Record<string, FeePlan>;
    gateway_name: string;
    is_in_page: boolean;
    is_pay_later: boolean;
    is_pay_now: boolean;
    label_button: string;
    nonce_value: string;
    title: string;
    amount_in_cents: number;
};

type AlmaBlockProps = {
    gatewaySettings: GatewaySettings;
    selectedFeePlan: string;
    setSelectedFeePlan: (value: string) => void;
    hasInPage: boolean;
    totalPrice: number;
    plans: Record<string, FeePlan>;
};

export const AlmaBlock: React.FC<AlmaBlockProps> = (
        {
            gatewaySettings,
            selectedFeePlan,
            setSelectedFeePlan,
            hasInPage,
            totalPrice,
            plans,
        }
) => {
    const labels = {};
    let values = [];
    for (const key of Object.keys(plans)) {
        const index = Object.keys(plans).indexOf(key);
        values.push(key);
        if (gatewaySettings.is_pay_later) {
            if (plans[key].deferredDays > 0) {
                labels[key] = "D+" + plans[key].deferredDays;
            } else if (plans[key].deferredMonths > 0) {
                labels[key] = "M+" + plans[key].deferredMonths;
            }
        } else {
            labels[key] = plans[key].installmentsCount + "x";
        }
    }

    const handleClick = (optionKey) => {
        setSelectedFeePlan(optionKey);
    };

    const label = (
            <div className="toggleButtonFieldLabel">{gatewaySettings.description}</div>
    );
    return (
            <IntlProvider locale="fr">
                {gatewaySettings.is_pay_now && <div className={"payNowLabel"}>{label}</div>}
                <div className={"buttonsContainer"}>
                    <div className={classNames({payNow: gatewaySettings.is_pay_now})}>
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
                                    feePlan={plans[selectedFeePlan]}
                                    amount={totalPrice}
                            />
                        </div>
                )}
            </IntlProvider>
    );
};
