// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import Button from '../Button';

test('Should render with icon and disabled', () => {
    const {asFragment} = render(<Button disabled={true} icon="su-plus-circle" onClick={jest.fn()} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Should call the callback on click', () => {
    const onClick = jest.fn();
    render(<Button icon="su-plus-circle" onClick={onClick} />);

    const button = screen.getByRole('button');
    const clickEvent = new MouseEvent('click', {bubbles: true, cancelable: true});

    button.dispatchEvent(clickEvent);

    expect(clickEvent.defaultPrevented).toEqual(true);
    expect(onClick).toBeCalled();
});
