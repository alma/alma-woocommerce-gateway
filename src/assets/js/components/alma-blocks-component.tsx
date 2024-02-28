/**
 * Checkout blocks component.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

import {useEffect, useState} from '@wordpress/element';
import {Badge, ToggleButtonsField} from "@alma/react-components";

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

    const labels = {
        a: 'j+15',
        b: '3x',
        c: '5x',
        d: '10x',
        e: '24x',
    }

    const values = ['a', 'b', 'c', 'd', 'e']

    const [value, setValue] = useState('b')

    console.log('Ca fonciiitonne !!', settings, setSelectedFeePlan)
    return <>
        <div>{settings?.description}</div>
        <Badge color={'orange'} label={'toto'}/>
        <div>
            <ToggleButtonsField
                options={values}
                optionLabel={(v) => labels[v]}
                optionKey={(v) => v}
                onChange={(v) => setValue(v)}
                value={value}
                label="default"
                wide={false}
                size={'md'}
                error=""
                legend="I am a legend"
            />
            {/*{settings.eligibilities && Object.values(settings.eligibilities).map((feePlan, index) =>*/}
            {/*        <div style={{*/}
            {/*            display: 'flex',*/}
            {/*            flexWrap: 'wrap',*/}
            {/*            // flexDirection: 'row',*/}
            {/*            justifyContent: 'center',*/}
            {/*            marginBottom: '24px',*/}
            {/*            paddingBottom: '12px',*/}
            {/*        }}>*/}
            {/*            < button*/}
            {/*                key={index}*/}
            {/*                style={{*/}
            {/*                    height: '50px',*/}
            {/*                    width: '50px',*/}
            {/*                    border: '1px solid gray',*/}
            {/*                    borderRadius: '16px',*/}
            {/*                    fontSize: '20px',*/}
            {/*                    lineHeight: '120%',*/}
            {/*                    fontWeight: '600',*/}
            {/*                    cursor: 'pointer',*/}
            {/*                    backgroundColor: 'white',*/}
            {/*                    transition: 'all 0.1s ease',*/}
            {/*                    color: 'black',*/}
            {/*                    padding: 'initial'*/}
            {/*                }}*/}
            {/*                onClick={(e) => handleClick(e, feePlan)}*/}
            {/*            >*/}
            {/*                <span>{feePlan.installmentsCount}x</span>*/}
            {/*            </button>*/}
            {/*        </div>*/}

            {/*    // <button onClick={(e) => handleClick(e, feePlan)} key={index}>{feePlan.installmentsCount}</button>*/}
            {/*)}*/}
        </div>
    </>
}