// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import Snackbar from '../Snackbar';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render an error snackbar', () => {
    const snackbar = mount(<Snackbar message="Something went wrong" onCloseClick={jest.fn()} type="error" />);
    expect(snackbar.render()).toMatchSnapshot();
});

test('Render an updated error snackbar', () => {
    const snackbar = mount(<Snackbar message="Something went wrong" onCloseClick={jest.fn()} type="error" />);
    snackbar.setProps({message: 'Something went wrong again'});
    expect(snackbar.render()).toMatchSnapshot();
});

test('Render a warning snackbar', () => {
    const snackbar = mount(
        <Snackbar message="Something unimportant went wrong" onCloseClick={jest.fn()} type="warning" />
    );

    expect(snackbar.render()).toMatchSnapshot();
});

test('Render a info snackbar', () => {
    const snackbar = mount(
        <Snackbar message="Something unimportant went wrong" onCloseClick={jest.fn()} type="info" />
    );

    expect(snackbar.render()).toMatchSnapshot();
});

test('Render a success snackbar', () => {
    const snackbar = mount(
        <Snackbar message="Something unimportant went wrong" onCloseClick={jest.fn()} type="success" />
    );

    expect(snackbar.render()).toMatchSnapshot();
});

test('Render a floating snackbar', () => {
    const snackbar = mount(
        <Snackbar
            behaviour="floating"
            icon="su-copy"
            message="3 blocks copied to clipboard"
            onCloseClick={jest.fn()}
            type="info"
        />
    );

    expect(snackbar.render()).toMatchSnapshot();
});

test('Render an error snackbar without close button', () => {
    const snackbar = mount(<Snackbar message="Something went wrong" type="error" />);
    expect(snackbar.render()).toMatchSnapshot();
});

test('Click the snackbar should call the onClick callback', () => {
    const clickSpy = jest.fn();
    const snackbar = shallow(<Snackbar message="Something went wrong" onClick={clickSpy} type="error" />);

    snackbar.simulate('click');

    expect(clickSpy).toBeCalledWith();
});

test('Call onCloseClick callback when close button is clicked', () => {
    const closeClickSpy = jest.fn();
    const snackbar = shallow(<Snackbar message="Something went wrong" onCloseClick={closeClickSpy} type="error" />);

    snackbar.find('Icon[name="su-times"]').prop('onClick')();

    expect(closeClickSpy).toBeCalledWith();
});
