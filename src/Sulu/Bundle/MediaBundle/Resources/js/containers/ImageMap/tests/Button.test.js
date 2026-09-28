// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import Button from '../Button';

test('Should render with icon and disabled', () => {
    const {container} = render(<Button disabled={true} icon="su-plus-circle" onClick={jest.fn()} />);

    expect(container).toMatchSnapshot();
});

test('Should call the callback on click', () => {
    const onClick = jest.fn();
    render(<Button icon="su-plus-circle" onClick={onClick} />);

    const clickEvent = new MouseEvent('click', {bubbles: true, cancelable: true});
    const preventDefaultSpy = jest.spyOn(clickEvent, 'preventDefault');

    fireEvent(screen.getByRole('button'), clickEvent);

    expect(preventDefaultSpy).toHaveBeenCalled();
    expect(onClick).toHaveBeenCalled();
});
