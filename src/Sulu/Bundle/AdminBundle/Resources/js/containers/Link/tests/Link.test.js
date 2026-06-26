// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {ResourceRequester} from '../../../services';
import Link from '../Link';
import linkTypeRegistry from '../registries/linkTypeRegistry';
import type {LinkValue} from '../types';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator');

jest.mock('../registries/linkTypeRegistry', () => ({
    getKeys: jest.fn(),
    getOverlay: jest.fn(),
    getOptions: jest.fn(),
    getTitle: jest.fn((key) => key.charAt(0).toUpperCase() + (key.slice(1))),
}));

function createOverlay(provider, hrefValue = '10') {
    return class Overlay extends React.Component<Object> {
        handleHrefClick = () => {
            if (this.props.onHrefChange) {
                this.props.onHrefChange(hrefValue);
            }
        };

        handleQueryClick = () => {
            if (this.props.onQueryChange) {
                this.props.onQueryChange('newQuery');
            }
        };

        handleAnchorClick = () => {
            if (this.props.onAnchorChange) {
                this.props.onAnchorChange('newAnchor');
            }
        };

        handleTargetClick = () => {
            if (this.props.onTargetChange) {
                this.props.onTargetChange('newTarget');
            }
        };

        handleTitleClick = () => {
            if (this.props.onTitleChange) {
                this.props.onTitleChange('newTitle');
            }
        };

        handleRelClick = () => {
            if (this.props.onRelChange) {
                this.props.onRelChange('newRel');
            }
        };

        render() {
            const {onCancel, onConfirm, open} = this.props;

            return (
                <div data-open={open ? 'true' : 'false'} data-testid={'overlay-' + provider}>
                    <button
                        aria-label={provider + '-set-href'}
                        onClick={this.handleHrefClick}
                        type="button"
                    >
                        Set href
                    </button>
                    <button
                        aria-label={provider + '-set-query'}
                        onClick={this.handleQueryClick}
                        type="button"
                    >
                        Set query
                    </button>
                    <button
                        aria-label={provider + '-set-anchor'}
                        onClick={this.handleAnchorClick}
                        type="button"
                    >
                        Set anchor
                    </button>
                    <button
                        aria-label={provider + '-set-target'}
                        onClick={this.handleTargetClick}
                        type="button"
                    >
                        Set target
                    </button>
                    <button
                        aria-label={provider + '-set-title'}
                        onClick={this.handleTitleClick}
                        type="button"
                    >
                        Set title
                    </button>
                    <button
                        aria-label={provider + '-set-rel'}
                        onClick={this.handleRelClick}
                        type="button"
                    >
                        Set rel
                    </button>
                    <button aria-label={provider + '-confirm'} onClick={onConfirm} type="button">
                        Confirm
                    </button>
                    <button aria-label={provider + '-cancel'} onClick={onCancel} type="button">
                        Cancel
                    </button>
                </div>
            );
        }
    };
}

const PageOverlay = createOverlay('page');
const MediaOverlay = createOverlay('media');
const ArticleOverlay = createOverlay('article');
const AccountOverlay = createOverlay('account');
const ExternalOverlay = createOverlay('external', 'https://example.org');

const overlays = {
    account: AccountOverlay,
    article: ArticleOverlay,
    external: ExternalOverlay,
    media: MediaOverlay,
    page: PageOverlay,
};

const overlayOptions = {
    page: {
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    },
    media: {
        title: 'Media',
        overlayTitle: 'Media Overlay',
        resourceKey: 'media',
        displayProperties: ['title'],
    },
};

beforeEach(() => {
    jest.clearAllMocks();
    ResourceRequester.get.mockReturnValue(Promise.resolve({title: 'Page 1'}));
    linkTypeRegistry.getKeys.mockReturnValue(['page']);
    linkTypeRegistry.getOptions.mockImplementation((key) => overlayOptions[key]);
    linkTypeRegistry.getOverlay.mockImplementation((key) => overlays[key]);
});

function renderLink(props: Object = {}) {
    const onChange = props.onChange || jest.fn();
    const onFinish = props.onFinish || jest.fn();

    return {
        onChange,
        onFinish,
        ...render(
            <Link
                locale={observable.box('en')}
                onChange={onChange}
                onFinish={onFinish}
                value={undefined}
                {...props}
            />
        ),
    };
}

async function selectProvider(user, providerTitle) {
    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText(providerTitle));
}

test('Render Link container incl. loading a selected value', async() => {
    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
    };

    renderLink({value});

    expect(await screen.findByText('Page 1')).toBeInTheDocument();
    expect(ResourceRequester.get).toHaveBeenCalledWith('pages', {
        id: '123-asdf-123',
        locale: expect.anything(),
    });
    expect(screen.getByTestId('overlay-page')).toHaveAttribute('data-open', 'false');
});

test('Open overlay on input click', async() => {
    const user = userEvent.setup();
    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
        anchor: 'TestAnchor',
        target: 'TestTarget',
        rel: 'TestRel',
    };

    renderLink({
        enableAnchor: true,
        enableRel: true,
        enableTarget: true,
        enableTitle: true,
        value,
    });

    expect(screen.getByTestId('overlay-page')).toHaveAttribute('data-open', 'false');

    await user.click(await screen.findByRole('button', {name: /Page 1/}));

    expect(screen.getByTestId('overlay-page')).toHaveAttribute('data-open', 'true');
});

test('Open overlay on provider change', async() => {
    const user = userEvent.setup();
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media']);
    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
        anchor: 'TestAnchor',
        target: 'TestTarget',
        rel: 'TestRel',
    };

    renderLink({
        enableAnchor: true,
        enableRel: true,
        enableTarget: true,
        enableTitle: true,
        value,
    });

    expect(screen.getByTestId('overlay-media')).toHaveAttribute('data-open', 'false');

    await selectProvider(user, 'Media');

    expect(screen.getByTestId('overlay-media')).toHaveAttribute('data-open', 'true');
});

test('Update values on overlay confirm', async() => {
    const user = userEvent.setup();
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media']);
    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
        query: 'TestQuery',
        anchor: 'TestAnchor',
        target: 'TestTarget',
    };
    const {onChange, onFinish} = renderLink({
        enableAnchor: true,
        enableQuery: true,
        enableRel: true,
        enableTarget: true,
        enableTitle: true,
        value,
    });

    await selectProvider(user, 'Media');
    await user.click(screen.getByLabelText('media-set-href'));
    await user.click(screen.getByLabelText('media-set-query'));
    await user.click(screen.getByLabelText('media-set-anchor'));
    await user.click(screen.getByLabelText('media-set-target'));
    await user.click(screen.getByLabelText('media-set-title'));
    await user.click(screen.getByLabelText('media-confirm'));

    expect(onChange).toHaveBeenCalledWith({
        title: 'newTitle',
        href: '10',
        provider: 'media',
        locale: 'en',
        query: 'newQuery',
        anchor: 'newAnchor',
        target: 'newTarget',
        rel: undefined,
    });
    expect(onFinish).toHaveBeenCalledWith();
});

test('Update values on overlay confirm with ExternalLinkTypeOverlay', async() => {
    const user = userEvent.setup();
    linkTypeRegistry.getKeys.mockReturnValue(['media', 'external']);
    linkTypeRegistry.getOptions.mockReturnValue(undefined);
    const value: LinkValue = {
        title: 'TestLink',
        href: '10',
        provider: 'media',
        locale: 'en',
        target: 'TestTarget',
    };
    const {onChange, onFinish} = renderLink({
        enableRel: true,
        enableTarget: true,
        enableTitle: true,
        value,
    });

    await selectProvider(user, 'External');
    await user.click(screen.getByLabelText('external-set-href'));
    await user.click(screen.getByLabelText('external-set-target'));
    await user.click(screen.getByLabelText('external-set-title'));
    await user.click(screen.getByLabelText('external-set-rel'));
    await user.click(screen.getByLabelText('external-confirm'));

    expect(onChange).toHaveBeenCalledWith({
        title: 'newTitle',
        href: 'https://example.org',
        provider: 'external',
        locale: 'en',
        target: 'newTarget',
        rel: 'newRel',
        anchor: undefined,
        query: undefined,
    });
    expect(onFinish).toHaveBeenCalledWith();
});

test('Invalidate values on RemoveButton click', async() => {
    const user = userEvent.setup();
    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
        anchor: 'TestAnchor',
        target: 'TestTarget',
        rel: 'TestRel',
    };
    const {onChange, onFinish} = renderLink({
        enableAnchor: true,
        enableRel: true,
        enableTarget: true,
        enableTitle: true,
        value,
    });

    expect(await screen.findByText('Page 1')).toBeInTheDocument();

    await user.click(screen.getByLabelText('su-trash-alt'));

    expect(onChange).toHaveBeenCalledWith({
        title: undefined,
        href: undefined,
        provider: undefined,
        locale: 'en',
        query: undefined,
        anchor: undefined,
        target: undefined,
        rel: undefined,
    });
    expect(onFinish).toHaveBeenCalledWith();
});

test('Display providers with "types" property', async() => {
    const user = userEvent.setup();
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media', 'article']);

    renderLink({
        enableAnchor: true,
        enableRel: true,
        enableTarget: true,
        enableTitle: true,
        types: ['page', 'article'],
    });

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.getByText('Page')).toBeInTheDocument();
    expect(screen.getByText('Article')).toBeInTheDocument();
    expect(screen.queryByText('Media')).not.toBeInTheDocument();
});

test('Display providers with "excluded_types" property', async() => {
    const user = userEvent.setup();
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media', 'article']);

    renderLink({
        enableAnchor: true,
        enableRel: true,
        enableTarget: true,
        enableTitle: true,
        excludedTypes: ['page', 'article'],
    });

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.getByText('Media')).toBeInTheDocument();
    expect(screen.queryByText('Page')).not.toBeInTheDocument();
    expect(screen.queryByText('Article')).not.toBeInTheDocument();
});

test('Display providers with "excluded_types" and "types" property', async() => {
    const user = userEvent.setup();
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media', 'article', 'account']);

    renderLink({
        enableAnchor: true,
        enableRel: true,
        enableTarget: true,
        enableTitle: true,
        excludedTypes: ['page', 'article'],
        types: ['media', 'account'],
    });

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.getByText('Media')).toBeInTheDocument();
    expect(screen.getByText('Account')).toBeInTheDocument();
    expect(screen.queryByText('Page')).not.toBeInTheDocument();
    expect(screen.queryByText('Article')).not.toBeInTheDocument();
});
