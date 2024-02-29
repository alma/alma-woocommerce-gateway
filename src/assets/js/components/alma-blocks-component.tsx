/**
 * Checkout blocks component.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

import {useState} from '@wordpress/element';
import '@alma/react-components/style.css';
import '@alma/react-components/global.css';
import {ToggleButtonsField} from "@alma/react-components";

type AlmaBlocksProps = {
    settings: any
    selectedFeePlan: string
    setSelectedFeePlan: (value: string) => void
}

export const AlmaBlocks: React.FC<AlmaBlocksProps> = ({settings, selectedFeePlan, setSelectedFeePlan}) => {

    const handleClick = (feePlan: any) => {
        console.log(feePlan);
        setSelectedFeePlan(`general_${feePlan.installmentsCount}_${feePlan.deferredDays}_${feePlan.deferredMonths}`)
    }

    const labels = {
        a: 'j+15',
        b: '3x',
        c: '5x',
        d: '10x',
        e: '24x',
    }

    const values = ['a', 'b', 'c', 'd', 'e']

    console.log('Ca fonciiitonne !!', settings, setSelectedFeePlan)
    return <>
        <ToggleButtonsField
            options={values}
            optionLabel={(v) => labels[v]}
            optionKey={(v) => v}
            onChange={(v) => handleClick(v)}
            value={selectedFeePlan}
            label={settings?.description}
            wide={false}
            size={'md'}
            error=""
        />
    </>
}