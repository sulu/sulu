// @flow
import React from 'react';
import type {Element} from 'react';

const WrapperComponent = (props: {children: Element<*>}) => {
    const component = React.Children.only(props.children);
    const [boundValue, setBoundValue] = React.useState(component.props.value);

    const wrappedOnChange = (newValue, ...remainingParameters) => {
        setBoundValue(newValue);
        component.props.onChange(newValue, ...remainingParameters);
    };

    return React.cloneElement(component, {value: boundValue, onChange: wrappedOnChange});
};

const bindValueToOnChange = (element: Element<*>) => {
    return <WrapperComponent>{element}</WrapperComponent>;
};

// our form components are implemented as controlled components. to test them with @testing-library/react, we
// need to update the "value" that is passed to the controlled component when its "onChange" callback is fired.
// if we dont do this, the component will read the old "value" when multiple events are triggered. for example,
// "userEvent.type()" will trigger an event for each keystroke.
export default bindValueToOnChange;
