/**
 * Checkout blocks component.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

import {useEffect} from '@wordpress/element';

type AlmaBlocksProps = {
    settings: any
    setSelectedFeePlan: (value: string) => void
}

export const AlmaBlocks: React.FC<AlmaBlocksProps> = ({settings, setSelectedFeePlan}) => {

    const handleClick = (e, feePlan) => {
        e.preventDefault()
        console.log(feePlan);
        setSelectedFeePlan(`general_${feePlan.installmentsCount}_${feePlan.deferredDays}_${feePlan.deferredMonths}`)
    }

    useEffect(() => {
        if (settings?.eligibilities) {
            setSelectedFeePlan(settings?.eligibilities.general_2_0_0.installmentsCount)
        }
    }, [settings]);

    console.log('Ca fonciiitonne !!', settings, setSelectedFeePlan)
    return <>
        <div>{settings?.description}</div>
        <div>
            {settings.eligibilities && Object.values(settings.eligibilities).map((feePlan, index) =>
                <button onClick={(e) => handleClick(e, feePlan)} key={index}>{feePlan.installmentsCount}</button>)}
        </div>
    </>
}