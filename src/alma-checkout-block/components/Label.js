import {Logo} from "@alma/react-components";

export const Label             = (props) => {
	const {components, title}  = props;
	const {PaymentMethodLabel} = components;
	const icon                 = < Logo style = {{width: 'auto', height: '1em'}} logo = "alma-orange" / > ;
	const text                 = < div > {title} < / div > ;

	return (
		< span className                      = "paymentMethodLabel" >
					< PaymentMethodLabel text = {text} icon = {icon} / >
				< / span >
	);
};
