// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceLocatorOpenUrl from '../ResourceLocatorOpenUrl';

jest.mock('sulu-admin-bundle/utils/Translator');

beforeEach(() => {
    window.open = jest.fn();
});

const renderOpenUrl = (props: Object = {}) => render(
    <ResourceLocatorOpenUrl
        locale="en"
        resourceId="uuid-1"
        resourceKey="articles"
        url="/route-url"
        webspaces={['main']}
        {...props}
    />
);

test('Should be disabled without resource id', () => {
    renderOpenUrl({resourceId: undefined});

    expect(screen.getByRole('button')).toBeDisabled();
});

test('Should be disabled without webspaces', () => {
    renderOpenUrl({webspaces: []});

    expect(screen.getByRole('button')).toBeDisabled();
});

test('Should be disabled if disabled prop is set', () => {
    renderOpenUrl({disabled: true});

    expect(screen.getByRole('button')).toBeDisabled();
});

test('Should open the redirecting url of the only webspace in a new tab', async() => {
    const user = userEvent.setup();

    renderOpenUrl();
    await user.click(screen.getByRole('button'));

    expect(window.open).toHaveBeenCalledWith(
        '/route-url?locale=en&resourceId=uuid-1&resourceKey=articles&webspace=main',
        '_blank',
        'noopener'
    );
});

test('Should not open anything while disabled', async() => {
    const user = userEvent.setup();

    renderOpenUrl({disabled: true});
    await user.click(screen.getByRole('button'));

    expect(window.open).not.toHaveBeenCalled();
});
