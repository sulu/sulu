// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps, getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import ContactAccountSelectionComponent from '../../../ContactAccountSelection';
import ContactAccountSelection from '../../fields/ContactAccountSelection';

jest.mock('../../../ContactAccountSelection', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    ((ContactAccountSelectionComponent: any): {mockClear: () => void}).mockClear();
});

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass props correctly to ContactAccountSelection component', () => {
    render(
        <ContactAccountSelection
            {...createProps({
                disabled: true,
                value: ['a1', 'c2'],
            })}
        />
    );

    const props: any = getLatestMockProps((ContactAccountSelectionComponent: any));

    expect(props.disabled).toEqual(true);
    expect(props.value).toEqual(['a1', 'c2']);
});

test('Call onChange and onFinish callbacks', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <ContactAccountSelection
            {...createProps({
                onChange: changeSpy,
                onFinish: finishSpy,
                value: ['a1', 'c2'],
            })}
        />
    );

    getLatestMockProps((ContactAccountSelectionComponent: any)).onChange(['a1', 'c6']);

    expect(changeSpy).toBeCalledWith(['a1', 'c6']);
    expect(finishSpy).toBeCalledWith();
});

test('Call onItemClick callback', () => {
    const router = {
        navigate: jest.fn(),
    };

    render(
        <ContactAccountSelection
            {...createProps({
                router: ((router: any): Object),
                value: ['a1', 'c2'],
            })}
        />
    );

    getLatestMockProps((ContactAccountSelectionComponent: any)).onItemClick('a1');
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.account_edit_form', {id: '1'});

    getLatestMockProps((ContactAccountSelectionComponent: any)).onItemClick('c2');
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.contact_edit_form', {id: '2'});
});
