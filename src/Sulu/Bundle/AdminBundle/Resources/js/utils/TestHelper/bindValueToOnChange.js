// @flow
import React from 'react';
import type {Element} from 'react';

type BindValueToOnChangeOptions = {
    valueArgIndex?: number,
};

const WrapperComponent = (props: {children: Element<*>, valueArgIndex: number}) => {
    const component = React.Children.only(props.children);
    const [boundValue, setBoundValue] = React.useState(component.props.value);
    const {valueArgIndex} = props;

    const wrappedOnChange = (...parameters) => {
        const newValue = parameters[valueArgIndex];
        setBoundValue(newValue);
        component.props.onChange(...parameters);
    };

    return React.cloneElement(component, {value: boundValue, onChange: wrappedOnChange});
};

const bindValueToOnChange = (element: Element<*>, options: BindValueToOnChangeOptions = {}) => {
    return <WrapperComponent valueArgIndex={options.valueArgIndex || 0}>{element}</WrapperComponent>;
};

// our form components are implemented as controlled components. to test them with @testing-library/react, we
// need to update the "value" that is passed to the controlled component when its "onChange" callback is fired.
// if we dont do this, the component will read the old "value" when multiple events are triggered. for example,
// "userEvent.type()" will trigger an event for each keystroke.
// https://github.com/testing-library/user-event/issues/549
export default bindValueToOnChange;
