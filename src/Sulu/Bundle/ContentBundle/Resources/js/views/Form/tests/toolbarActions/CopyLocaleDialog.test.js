// @flow
import React from 'react';
import {mount} from 'enzyme';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import CopyLocaleDialog from '../../toolbarActions/CopyLocaleDialog';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        postWithId: jest.fn(() => Promise.resolve()),
    },
}));

test('Render the dialog with given props', () => {
    const copyLocaleDialog = mount(
        <CopyLocaleDialog
            contentLocales={['de']}
            id={3}
            locale="de"
            locales={['en', 'de']}
            onClose={jest.fn()}
            open={true}
            webspace="sulu_io"
        />
    );

    expect(copyLocaleDialog.find('Portal').at(2).render()).toMatchSnapshot();
});

test('Call onClose callback if cancel is clicked', () => {
    const closeSpy = jest.fn();

    const copyLocaleDialog = mount(
        <CopyLocaleDialog
            contentLocales={['de']}
            id={3}
            locale="de"
            locales={['en', 'de']}
            onClose={closeSpy}
            open={true}
            webspace="sulu_io"
        />
    );

    copyLocaleDialog.find('Button[skin="secondary"]').simulate('click');

    expect(closeSpy).toBeCalledWith(false);
});

test('Copy locales and call onClose callback if confirm is clicked', () => {
    const closeSpy = jest.fn();
    const postWithIdPromise = Promise.resolve();
    ResourceRequester.postWithId.mockReturnValue(postWithIdPromise);

    const copyLocaleDialog = mount(
        <CopyLocaleDialog
            contentLocales={['de']}
            id={3}
            locale="en"
            locales={['en', 'de', 'jp', 'cn']}
            onClose={closeSpy}
            open={true}
            webspace="sulu_io"
        />
    );

    copyLocaleDialog.find('Checkbox[value="cn"]').prop('onChange')(true, 'cn');
    copyLocaleDialog.find('Checkbox[value="cn"]').prop('onChange')(true, 'jp');
    copyLocaleDialog.find('Button[skin="primary"]').simulate('click');

    expect(copyLocaleDialog.find('Dialog').prop('confirmLoading')).toEqual(true);
    expect(ResourceRequester.postWithId).toBeCalledWith(
        'pages',
        3,
        undefined,
        {action: 'copy-locale', dest: ['cn', 'jp'], locale: 'en', webspace: 'sulu_io'}
    );

    return postWithIdPromise.then(() => {
        copyLocaleDialog.update();
        expect(copyLocaleDialog.find('Dialog').prop('confirmLoading')).toEqual(false);
        expect(closeSpy).toBeCalledWith(true);
    });
});

test('Reset selectedLocales if dialog is closed', () => {
    const closeSpy = jest.fn();

    const copyLocaleDialog = mount(
        <CopyLocaleDialog
            contentLocales={['de']}
            id={3}
            locale="en"
            locales={['en', 'de', 'jp', 'cn']}
            onClose={closeSpy}
            open={true}
            webspace="sulu_io"
        />
    );

    expect(copyLocaleDialog.find('input[checked=true]')).toHaveLength(0);

    copyLocaleDialog.find('Checkbox[value="cn"]').prop('onChange')(true, 'cn');
    copyLocaleDialog.find('Checkbox[value="cn"]').prop('onChange')(true, 'jp');
    copyLocaleDialog.update();
    expect(copyLocaleDialog.find('input[checked=true]')).toHaveLength(2);

    copyLocaleDialog.setProps({open: false});
    copyLocaleDialog.update();
    expect(copyLocaleDialog.find('input[checked=true]')).toHaveLength(0);
});
