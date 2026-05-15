// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ExternalLinkTypeOverlay from '../../overlays/ExternalLinkTypeOverlay';

const defaultOptions = {
    displayProperties: [],
    resourceKey: '',
};

function renderExternalLinkTypeOverlay(props: Object = {}) {
    return render(
        <ExternalLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onRelChange={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={defaultOptions}
            rel={undefined}
            target={undefined}
            title={undefined}
            {...props}
        />
    );
}

function getField(label: RegExp | string) {
    const labelElement = screen.getByText(label);
    const field = labelElement.closest('.field');
    if (!field) {
        throw new Error('Expected field for label "' + String(label) + '"');
    }

    return field;
}

function getUrlInput() {
    return within(getField(/sulu_admin.link_url/)).getByRole('textbox');
}

function getGridElement() {
    const body = document.body;

    if (!body) {
        throw new Error('Expected document.body to exist');
    }

    return body.querySelector('.grid');
}

async function selectUrlProtocol(user, protocol: string) {
    await user.click(within(getField(/sulu_admin.link_url/)).getByLabelText('su-angle-down'));
    await user.click(screen.getByText(protocol));
}

test('Render overlay with an undefined URL', () => {
    renderExternalLinkTypeOverlay({href: undefined});

    expect(getGridElement()).toMatchSnapshot();
});

test('Render overlay with mailto URL', () => {
    renderExternalLinkTypeOverlay({href: 'mailto:test@example.org?subject=Subject&body=Body'});

    expect(getGridElement()).toMatchSnapshot();
});

test('Render overlay with a URL', () => {
    renderExternalLinkTypeOverlay({href: 'http://www.sulu.io'});

    expect(getGridElement()).toMatchSnapshot();
});

test('Pass correct props to Dialog', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();
    const confirmSpy = jest.fn();

    const {rerender} = render(
        <ExternalLinkTypeOverlay
            href="http://www.sulu.io"
            onCancel={cancelSpy}
            onConfirm={confirmSpy}
            onHrefChange={jest.fn()}
            onRelChange={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={false}
            options={defaultOptions}
            rel={undefined}
            target={undefined}
            title={undefined}
        />
    );

    expect(screen.queryByText('sulu_admin.link')).not.toBeInTheDocument();

    rerender(
        <ExternalLinkTypeOverlay
            href="http://www.sulu.io"
            onCancel={cancelSpy}
            onConfirm={confirmSpy}
            onHrefChange={jest.fn()}
            onRelChange={jest.fn()}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={defaultOptions}
            rel={undefined}
            target={undefined}
            title={undefined}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.confirm'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(cancelSpy).toBeCalled();
    expect(confirmSpy).toBeCalled();
});

test('Display given URL with query parameters in href input', () => {
    renderExternalLinkTypeOverlay({
        href: 'http://www.sulu.io/contact-us?param=value-1',
        target: '_blank',
    });

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
    expect(urlChangeSpy).not.toBeCalled();
});

test('Fields should change immediately after protocol was changed', async() => {
    const user = userEvent.setup();
    renderExternalLinkTypeOverlay({target: '_blank'});

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
        target: '_blank',
    });

    const urlInput = getUrlInput();
    await user.clear(urlInput);
    await user.type(urlInput, 'test@example.org');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org');
    await user.tab();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toBeCalledWith('_self');

    const mailSubjectInput = within(getField('sulu_admin.mail_subject')).getByRole('textbox');
    await user.type(mailSubjectInput, 'Subject Line');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org?subject=Subject%20Line');
    await user.tab();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org?subject=Subject%20Line');

    const mailBodyInput = within(getField('sulu_admin.mail_body')).getByRole('textbox');
    await user.type(mailBodyInput, 'Body Text');
    expect(urlChangeSpy).not.toBeCalledWith('mailto:test@example.org?subject=Subject%20Line&body=Body%20Text');
    await user.tab();
    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org?subject=Subject%20Line&body=Body%20Text');
});

test('Should not include mail subject and body in URL after switching to another protocol', async() => {
    const user = userEvent.setup();
    const urlChangeSpy = jest.fn();

    renderExternalLinkTypeOverlay({
        href: 'mailto:test@example.org?subject=Subject&body=Body%20Text',
        onHrefChange: urlChangeSpy,
        target: '_blank',
    });

    await user.click(getUrlInput());
    await user.tab();
    expect(urlChangeSpy).toHaveBeenNthCalledWith(1, 'mailto:test@example.org?subject=Subject&body=Body%20Text');

    await selectUrlProtocol(user, 'https://');
    expect(urlChangeSpy).toHaveBeenLastCalledWith('https://test@example.org');
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

    const urlInput = getUrlInput();
    await user.clear(urlInput);
    await user.type(urlInput, 'mailto:test@example.org');
    await user.tab();

    expect(urlChangeSpy).toBeCalledWith('mailto:test@example.org');
    expect(targetChangeSpy).toBeCalledWith('_self');
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

    const urlInput = getUrlInput();
    await user.clear(urlInput);
    await user.type(urlInput, 'http://sulu.io');
    await user.tab();

    expect(urlChangeSpy).toBeCalledWith('http://sulu.io');
    expect(targetChangeSpy).not.toBeCalled();
});

test('Rel value should be transformed correctly', async() => {
    const user = userEvent.setup();
    const relChangeSpy = jest.fn();

    const {rerender} = renderExternalLinkTypeOverlay({
        onRelChange: relChangeSpy,
        rel: 'noopener  noreferrer ',
    });

    await user.click(screen.getByLabelText('sulu_admin.no_follow'));
    expect(relChangeSpy).toBeCalledWith('noopener noreferrer nofollow');

    rerender(
        <ExternalLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onRelChange={relChangeSpy}
            onTargetChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={defaultOptions}
            rel="noopener noreferrer nofollow"
            target={undefined}
            title={undefined}
        />
    );

    await user.click(screen.getByLabelText('sulu_admin.no_follow'));
    expect(relChangeSpy).toBeCalledWith('noopener noreferrer');
});
