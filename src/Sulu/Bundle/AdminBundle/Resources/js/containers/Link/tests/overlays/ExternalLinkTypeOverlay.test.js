// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ExternalLinkTypeOverlay from '../../overlays/ExternalLinkTypeOverlay';

jest.mock('../../../../utils/Translator');

const OPTIONS = {
    displayProperties: [],
    resourceKey: '',
};

function renderExternalLinkTypeOverlay(props: Object = {}) {
    const defaultProps = {
        href: undefined,
        onCancel: jest.fn(),
        onConfirm: jest.fn(),
        onHrefChange: jest.fn(),
        onRelChange: jest.fn(),
        onTargetChange: jest.fn(),
        onTitleChange: jest.fn(),
        open: true,
        options: OPTIONS,
        rel: undefined,
        target: undefined,
        title: undefined,
    };
    const allProps = {...defaultProps, ...props};
    const view = render(<ExternalLinkTypeOverlay {...allProps} />);

    return {
        ...allProps,
        ...view,
        rerenderExternalLinkTypeOverlay: (nextProps: Object) => {
            view.rerender(<ExternalLinkTypeOverlay {...allProps} {...nextProps} />);
        },
    };
}

function getUrlInput(): HTMLInputElement {
    const input = document.querySelector('input[type="text"]');

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('URL input was not rendered.');
    }

    return input;
}

function getInputForField(label: string): HTMLInputElement {
    const labelElement = screen.getByText(label);
    const input = labelElement.parentElement && labelElement.parentElement.querySelector('input');

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('Input for "' + label + '" was not rendered.');
    }

    return input;
}

function getTextAreaForField(label: string): HTMLTextAreaElement {
    const labelElement = screen.getByText(label);
    const textArea = labelElement.parentElement && labelElement.parentElement.querySelector('textarea');

    if (!(textArea instanceof HTMLTextAreaElement)) {
        throw new Error('Text area for "' + label + '" was not rendered.');
    }

    return textArea;
}

async function selectUrlProtocol(user, protocol: string) {
    await user.click(screen.getAllByLabelText('su-angle-down')[0]);
    await user.click(screen.getByText(protocol));
}

test('Render overlay with an undefined URL', () => {
    renderExternalLinkTypeOverlay();

    expect(screen.getByText('sulu_admin.link')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.link_url *')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.link_target *')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.link_title')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.no_follow')).toBeInTheDocument();
    expect(getUrlInput()).toHaveValue('');
    expect(screen.getByRole('button', {name: 'sulu_admin.confirm'})).toBeDisabled();
});

test('Render overlay with mailto URL', () => {
    renderExternalLinkTypeOverlay({
        href: 'mailto:test@example.org?subject=Subject&body=Body',
    });

    expect(screen.getByText('mailto:')).toBeInTheDocument();
    expect(getUrlInput()).toHaveValue('test@example.org');
    expect(screen.getByText('sulu_admin.mail_subject')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Subject')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.mail_body')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Body')).toBeInTheDocument();
});

test('Render overlay with a URL', () => {
    renderExternalLinkTypeOverlay({
        href: 'http://www.sulu.io',
    });

    expect(screen.getByText('http://')).toBeInTheDocument();
    expect(getUrlInput()).toHaveValue('www.sulu.io');
    expect(screen.getByText('sulu_admin.link_target *')).toBeInTheDocument();
});

test('Pass correct props to Dialog', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();
    const confirmSpy = jest.fn();

    renderExternalLinkTypeOverlay({
        href: 'https://sulu.io',
        onCancel: cancelSpy,
        onConfirm: confirmSpy,
        open: false,
    });

    expect(screen.queryByText('sulu_admin.link')).not.toBeInTheDocument();

    renderExternalLinkTypeOverlay({
        href: 'https://sulu.io',
        onCancel: cancelSpy,
        onConfirm: confirmSpy,
    });

    await user.click(screen.getByRole('button', {name: 'sulu_admin.confirm'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(confirmSpy).toHaveBeenCalledTimes(1);
    expect(cancelSpy).toHaveBeenCalledTimes(1);
});

test('Display given URL with query parameters in href input', () => {
    renderExternalLinkTypeOverlay({
        href: 'http://www.sulu.io/contact-us?param=value-1',
        target: '_blank',
    });

    expect(screen.getByText('http://')).toBeInTheDocument();
    expect(getUrlInput()).toHaveValue('www.sulu.io/contact-us?param=value-1');
});

test('Do not call onHrefChange handler if input did not loose focus', async() => {
    const user = userEvent.setup();
    const urlChangeSpy = jest.fn();

    renderExternalLinkTypeOverlay({
        onHrefChange: urlChangeSpy,
        target: '_blank',
    });

    await user.type(getUrlInput(), 'www.sulu.io');

    expect(urlChangeSpy).not.toHaveBeenCalled();
});

test('Fields should change immediately after protocol was changed', async() => {
    const user = userEvent.setup();

    renderExternalLinkTypeOverlay({
        target: '_blank',
    });

    expect(screen.getByText('sulu_admin.link_target *')).toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.mail_subject')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.mail_body')).not.toBeInTheDocument();

    await selectUrlProtocol(user, 'mailto:');

    expect(screen.queryByText('sulu_admin.link_target *')).not.toBeInTheDocument();
    expect(screen.getByText('sulu_admin.mail_subject')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.mail_body')).toBeInTheDocument();
});

test('Call onHrefChange with URL that includes mail subject and mail body for mailto links', async() => {
    const user = userEvent.setup();
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    renderExternalLinkTypeOverlay({
        href: 'mailto:bla@example.org',
        onHrefChange: urlChangeSpy,
        onTargetChange: targetChangeSpy,
        onTitleChange: undefined,
        target: '_blank',
    });

    await user.clear(getUrlInput());
    await user.type(getUrlInput(), 'test@example.org');
    expect(urlChangeSpy).not.toHaveBeenCalledWith('mailto:test@example.org');
    fireEvent.blur(getUrlInput());
    expect(urlChangeSpy).toHaveBeenCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toHaveBeenCalledWith('_self');

    await user.type(getInputForField('sulu_admin.mail_subject'), 'Subject Line');
    expect(urlChangeSpy).not.toHaveBeenCalledWith('mailto:test@example.org?subject=Subject%20Line');
    fireEvent.blur(getInputForField('sulu_admin.mail_subject'));
    expect(urlChangeSpy).toHaveBeenCalledWith('mailto:test@example.org?subject=Subject%20Line');

    await user.type(getTextAreaForField('sulu_admin.mail_body'), 'Body Text');
    expect(urlChangeSpy).not.toHaveBeenCalledWith(
        'mailto:test@example.org?subject=Subject%20Line&body=Body%20Text'
    );
    fireEvent.blur(getTextAreaForField('sulu_admin.mail_body'));
    expect(urlChangeSpy).toHaveBeenCalledWith(
        'mailto:test@example.org?subject=Subject%20Line&body=Body%20Text'
    );
});

test('Should not include mail subject and body in URL after switching to another protocol', async() => {
    const user = userEvent.setup();
    const urlChangeSpy = jest.fn();

    renderExternalLinkTypeOverlay({
        href: 'mailto:test@example.org?subject=Subject&body=Body%20Text',
        onHrefChange: urlChangeSpy,
        onTitleChange: undefined,
        target: '_blank',
    });

    fireEvent.blur(getUrlInput());
    expect(urlChangeSpy).toHaveBeenNthCalledWith(1, 'mailto:test@example.org?subject=Subject&body=Body%20Text');

    await selectUrlProtocol(user, 'https://');
    expect(urlChangeSpy).toHaveBeenNthCalledWith(2, 'https://test@example.org');
});

test('Reset target to self when a mailto link is entered', async() => {
    const user = userEvent.setup();
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    renderExternalLinkTypeOverlay({
        href: 'http://www.sulu.io',
        onHrefChange: urlChangeSpy,
        onTargetChange: targetChangeSpy,
        target: '_blank',
    });

    await user.clear(getUrlInput());
    await user.type(getUrlInput(), 'mailto:test@example.org');
    fireEvent.blur(getUrlInput());

    expect(urlChangeSpy).toHaveBeenCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toHaveBeenCalledWith('_self');
});

test('Should not reset target to self when a non-mail URL is entered', async() => {
    const user = userEvent.setup();
    const targetChangeSpy = jest.fn();
    const urlChangeSpy = jest.fn();

    renderExternalLinkTypeOverlay({
        href: 'http://www.sulu.io',
        onHrefChange: urlChangeSpy,
        onTargetChange: targetChangeSpy,
        target: '_blank',
    });

    await user.clear(getUrlInput());
    await user.type(getUrlInput(), 'sulu.io');
    fireEvent.blur(getUrlInput());

    expect(urlChangeSpy).toHaveBeenCalledWith('http://sulu.io');
    expect(targetChangeSpy).not.toHaveBeenCalled();
});

test('Rel value should be transformed correctly', async() => {
    const user = userEvent.setup();
    const relChangeSpy = jest.fn();
    const {rerenderExternalLinkTypeOverlay} = renderExternalLinkTypeOverlay({
        onRelChange: relChangeSpy,
        rel: 'noopener  noreferrer ',
    });

    await user.click(screen.getByRole('checkbox'));
    expect(relChangeSpy).toHaveBeenCalledWith('noopener noreferrer nofollow');

    rerenderExternalLinkTypeOverlay({
        rel: 'noopener noreferrer nofollow',
    });

    await user.click(screen.getByRole('checkbox'));
    expect(relChangeSpy).toHaveBeenCalledWith('noopener noreferrer');
});
