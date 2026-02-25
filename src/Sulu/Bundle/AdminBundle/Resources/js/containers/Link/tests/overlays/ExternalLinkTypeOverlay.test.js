// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import ExternalLinkTypeOverlay from '../../overlays/ExternalLinkTypeOverlay';
import Dialog from '../../../../components/Dialog';
import Input from '../../../../components/Input';
import TextArea from '../../../../components/TextArea';
import Toggler from '../../../../components/Toggler';
import Url from '../../../../components/Url';
import findMockCallArg from '../../../../utils/TestHelper/findMockCallArg';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../components/Dialog', () => jest.fn(function DialogMock({children, open}) {
    if (!open) {
        return null;
    }

    return <div data-testid="dialog">{children}</div>;
}));

jest.mock('../../../../components/Form', () => {
    const React = require('react');

    const FormMock: any = jest.fn(function FormMock({children}) {
        return React.createElement('div', {'data-testid': 'form'}, children);
    });

    FormMock.Field = jest.fn(function FormFieldMock({children, label}) {
        return React.createElement(
            'div',
            {'data-testid': 'form-field'},
            label ? React.createElement('span', {'data-testid': 'form-field-label'}, label) : null,
            children
        );
    });

    return FormMock;
});

jest.mock('../../../../components/Input', () => jest.fn(function InputMock() {
    return <div data-testid="input" />;
}));

jest.mock('../../../../components/TextArea', () => jest.fn(function TextAreaMock() {
    return <div data-testid="text-area" />;
}));

jest.mock('../../../../components/Toggler', () => jest.fn(function TogglerMock({children}) {
    return <div data-testid="toggler">{children}</div>;
}));

jest.mock('../../../../components/SingleSelect', () => {
    const React = require('react');

    const SingleSelectMock = function SingleSelectMock({children}) {
        return React.createElement('div', {'data-testid': 'single-select'}, children);
    };

    SingleSelectMock.Option = function SingleSelectOptionMock({children}) {
        return React.createElement('div', {'data-testid': 'single-select-option'}, children);
    };

    return SingleSelectMock;
});

jest.mock('../../../../components/Url', () => {
    const React = require('react');

    return jest.fn(function UrlMock({defaultProtocol, onProtocolChange, value}) {
        React.useEffect(() => {
            if (!onProtocolChange) {
                return;
            }

            if (typeof value === 'string' && value.startsWith('mailto:')) {
                onProtocolChange('mailto:');
                return;
            }

            onProtocolChange(defaultProtocol);
        }, [defaultProtocol, onProtocolChange, value]);

        return <div data-testid="url">{value}</div>;
    });
});

function createProps(overrides: Object = {}) {
    return {
        href: undefined,
        onCancel: jest.fn(),
        onConfirm: jest.fn(),
        onHrefChange: jest.fn(),
        onRelChange: jest.fn(),
        onTargetChange: jest.fn(),
        onTitleChange: jest.fn(),
        open: true,
        options: {
            displayProperties: [],
            resourceKey: '',
        },
        rel: undefined,
        target: undefined,
        title: undefined,
        ...overrides,
    };
}

function getLatestDialogProps() {
    return getLatestMockProps((Dialog: any));
}

function getLatestUrlProps() {
    return getLatestMockProps((Url: any));
}

function getMailSubjectInputProps() {
    return findMockCallArg((Input: any), ([props]) => typeof props.onBlur === 'function');
}

function getLatestTextAreaProps() {
    return getLatestMockProps((TextArea: any));
}

function getLatestTogglerProps() {
    return getLatestMockProps((Toggler: any));
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render overlay with an undefined URL', () => {
    const {asFragment} = render(<ExternalLinkTypeOverlay {...createProps()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render overlay with mailto URL', () => {
    const {asFragment} = render(
        <ExternalLinkTypeOverlay {...createProps({href: 'mailto:test@example.org?subject=Subject&body=Body'})} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render overlay with a URL', () => {
    const {asFragment} = render(<ExternalLinkTypeOverlay {...createProps({href: 'http://www.sulu.io'})} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Pass correct props to Dialog', () => {
    const cancelSpy = jest.fn();
    const confirmSpy = jest.fn();

    render(<ExternalLinkTypeOverlay {...createProps({onCancel: cancelSpy, onConfirm: confirmSpy, open: false})} />);

    expect(getLatestDialogProps().onCancel).toEqual(cancelSpy);
    expect(getLatestDialogProps().onConfirm).toEqual(confirmSpy);
    expect(getLatestDialogProps().open).toEqual(false);
});

test('Display given URL with query parameters in href input', () => {
    render(
        <ExternalLinkTypeOverlay
            {...createProps({href: 'http://www.sulu.io/contact-us?param=value-1', target: '_blank'})}
        />
    );

    expect(getLatestUrlProps().value).toEqual('http://www.sulu.io/contact-us?param=value-1');
});

test('Do not call onHrefChange handler if input did not loose focus', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    render(
        <ExternalLinkTypeOverlay
            {...createProps({onHrefChange: urlChangeSpy, onTargetChange: targetChangeSpy, target: '_blank'})}
        />
    );

    act(() => {
        getLatestUrlProps().onChange('http://www.sulu.io');
    });

    expect(urlChangeSpy).not.toBeCalled();
});

test('Fields should change immediately after protocol was changed', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    render(
        <ExternalLinkTypeOverlay
            {...createProps({onHrefChange: urlChangeSpy, onTargetChange: targetChangeSpy, target: '_blank'})}
        />
    );

    expect(screen.getByText('sulu_admin.link_target')).toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.mail_subject')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.mail_body')).not.toBeInTheDocument();

    act(() => {
        getLatestUrlProps().onProtocolChange('mailto:');
    });

    expect(screen.queryByText('sulu_admin.link_target')).not.toBeInTheDocument();
    expect(screen.getByText('sulu_admin.mail_subject')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.mail_body')).toBeInTheDocument();
});

test('Call onHrefChange with URL that includes mail subject and mail body for mailto links', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    render(
        <ExternalLinkTypeOverlay
            {...createProps({
                href: 'mailto:bla@example.org',
                onHrefChange: urlChangeSpy,
                onTargetChange: targetChangeSpy,
                target: '_blank',
            })}
        />
    );

    act(() => {
        getLatestUrlProps().onChange('mailto:test@example.org');
        getLatestUrlProps().onProtocolChange('mailto:');
    });

    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org');

    act(() => {
        getLatestUrlProps().onBlur();
    });

    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toBeCalledWith('_self');

    const mailSubjectInputProps = getMailSubjectInputProps();
    expect(mailSubjectInputProps).toBeDefined();

    act(() => {
        mailSubjectInputProps.onChange('Subject Line');
    });

    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org?subject=Subject%20Line');

    act(() => {
        mailSubjectInputProps.onBlur();
    });

    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org?subject=Subject%20Line');

    act(() => {
        getLatestTextAreaProps().onChange('Body Text');
    });

    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org?subject=Subject%20Line&body=Body%20Text');

    act(() => {
        getLatestTextAreaProps().onBlur();
    });

    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org?subject=Subject%20Line&body=Body%20Text');
});

test('Should not include mail subject and body in URL after switching to another protocol', () => {
    const urlChangeSpy = jest.fn();

    render(
        <ExternalLinkTypeOverlay
            {...createProps({
                href: 'mailto:test@example.org?subject=Subject&body=Body%20Text',
                onHrefChange: urlChangeSpy,
                target: '_blank',
            })}
        />
    );

    act(() => {
        getLatestUrlProps().onBlur();
    });

    expect(urlChangeSpy).toHaveBeenNthCalledWith(1, 'mailto:test@example.org?subject=Subject&body=Body%20Text');

    act(() => {
        getLatestUrlProps().onChange('https://test@example.org');
        getLatestUrlProps().onProtocolChange('https://');
        getLatestUrlProps().onBlur();
    });

    expect(urlChangeSpy).toHaveBeenNthCalledWith(2, 'https://test@example.org');
});

test('Reset target to self when a mailto link is entered', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    render(
        <ExternalLinkTypeOverlay
            {...createProps({
                href: 'http://www.sulu.io',
                onHrefChange: urlChangeSpy,
                onTargetChange: targetChangeSpy,
                target: '_blank',
            })}
        />
    );

    act(() => {
        getLatestUrlProps().onChange('mailto:test@example.org');
        getLatestUrlProps().onBlur();
    });

    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toBeCalledWith('_self');
});

test('Should not reset target to self when a non-mail URL is entered', () => {
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    render(
        <ExternalLinkTypeOverlay
            {...createProps({
                href: 'http://www.sulu.io',
                onHrefChange: urlChangeSpy,
                onTargetChange: targetChangeSpy,
                target: '_blank',
            })}
        />
    );

    act(() => {
        getLatestUrlProps().onChange('http://sulu.io');
        getLatestUrlProps().onBlur();
    });

    expect(urlChangeSpy).toBeCalledWith('http://sulu.io');
    expect(targetChangeSpy).not.toBeCalled();
});

test('Rel value should be transformed correctly', () => {
    const urlChangeSpy = jest.fn();
    const relChangeSpy = jest.fn();

    render(
        <ExternalLinkTypeOverlay
            {...createProps({
                onHrefChange: urlChangeSpy,
                onRelChange: relChangeSpy,
                rel: 'noopener  noreferrer ',
            })}
        />
    );

    act(() => {
        getLatestTogglerProps().onChange(true);
    });

    expect(relChangeSpy).toBeCalledWith('noopener noreferrer nofollow');

    act(() => {
        getLatestTogglerProps().onChange(false);
    });

    expect(relChangeSpy).toBeCalledWith('noopener noreferrer');
});
