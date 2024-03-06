import React from 'react'
import {IntlProvider} from "react-intl";
import { InstallmentsTotal } from "./InstallmentsTotal";
import {render, screen} from '@testing-library/react'
describe('InstallmentsTotal', () => {
  it('should display the right total amount', () => {
    render(<IntlProvider locale={"fr"}><InstallmentsTotal totalAmount={1000}/></IntlProvider>)
    expect(screen.getByText('10,00 €')).toBeTruthy()
  })
})
