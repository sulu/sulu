// @flow
import React from 'react';
import {mount} from 'enzyme';
import pretty from 'pretty';
import GhostDialog from '../GhostDialog';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(),
}));

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('Should render a Dialog', () => {
    const body = document.body;

    mount(<GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={jest.fn()} open={true} />);

    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('Should call onCancel callback if user chooses not to copy content', () => {
    const cancelSpy = jest.fn();
    const ghostDialog = mount(
        <GhostDialog locales={['en', 'de']} onCancel={cancelSpy} onConfirm={jest.fn()} open={true} />
    );

    ghostDialog.find('Button[skin="secondary"]').simulate('click');

    expect(cancelSpy).toBeCalledWith();
});

test('Should call onConfirm callback with chosen locale if user chooses to copy content', () => {
    const confirmSpy = jest.fn();
    const ghostDialog = mount(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />
    );

    ghostDialog.find('SingleSelect').prop('onChange')('de');
    ghostDialog.find('Button[skin="primary"]').simulate('click');

    expect(confirmSpy).toBeCalledWith('de');
});
