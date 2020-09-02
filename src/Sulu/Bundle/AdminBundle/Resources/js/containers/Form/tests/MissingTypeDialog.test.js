// @flow
import React from 'react';
import {mount} from 'enzyme';
import MissingTypeDialog from '../MissingTypeDialog';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should render a Dialog', () => {
    const types = {
        homepage: {key: 'homepage', title: 'Homepage'},
    };

    const missingTypeDialog = mount(
        <MissingTypeDialog onCancel={jest.fn()} onConfirm={jest.fn()} open={true} types={types} />
    );

    expect(missingTypeDialog.render()).toMatchSnapshot();
});

test('Should call onCancel callback if user chooses not to change type', () => {
    const cancelSpy = jest.fn();
    const types = {
        homepage: {key: 'homepage', title: 'Homepage'},
    };

    const missingTypeDialog = mount(
        <MissingTypeDialog onCancel={cancelSpy} onConfirm={jest.fn()} open={true} types={types} />
    );

    missingTypeDialog.find('Button[skin="secondary"]').simulate('click');

    expect(cancelSpy).toBeCalledWith();
});

test('Should call onConfirm callback with chosen type if user chooses to change type', () => {
    const confirmSpy = jest.fn();
    const types = {
        homepage: {key: 'homepage', title: 'Homepage'},
    };

    const missingTypeDialog = mount(
        <MissingTypeDialog onCancel={jest.fn()} onConfirm={confirmSpy} open={true} types={types} />
    );

    expect(missingTypeDialog.find('Dialog').prop('confirmDisabled')).toEqual(true);
    missingTypeDialog.find('DisplayValue').simulate('click');
    missingTypeDialog.update();
    missingTypeDialog.find('SingleSelect Option[children="Homepage"] button').simulate('click');
    expect(missingTypeDialog.find('Dialog').prop('confirmDisabled')).toEqual(false);
    missingTypeDialog.find('Button[skin="primary"]').simulate('click');

    expect(confirmSpy).toBeCalledWith('homepage');
});
