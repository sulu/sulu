// @flow
/* eslint-disable max-len */
import React from 'react';
import {act, fireEvent, render as rtlRender} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {RequestPromise} from 'sulu-admin-bundle/services/ResourceRequester';
import MediaCardOverviewAdapter from '../../List/adapters/MediaCardOverviewAdapter';
import MediaCollection from '../MediaCollection';

const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const SETTINGS_KEY = 'media_collection_test';
const USER_SETTINGS_KEY = 'media_overview';

const eventHandlerByName = {
    blur: 'onBlur',
    change: 'onChange',
    click: 'onClick',
    focus: 'onFocus',
    keyDown: 'onKeyDown',
    submit: 'onSubmit',
};

const getNodeTypeName = (type: any): string => {
    if (typeof type === 'string') {
        return type;
    }

    if (!type) {
        return '';
    }

    return type.displayName || type.name || '';
};

const getChildrenText = (children: any): string => {
    if (typeof children === 'string' || typeof children === 'number') {
        return String(children);
    }

    if (Array.isArray(children)) {
        return children.map(getChildrenText).join('');
    }

    if (!children || !children.props) {
        return '';
    }

    return getChildrenText(children.props.children);
};

const getReactFiberKey = (element: HTMLElement): ?string => (
    Object.keys(element).find((key) => key.startsWith('__reactFiber$') || key.startsWith('__reactInternalInstance$'))
);

const getRootFiber = (container: HTMLElement): any => {
    const rootElement = ((container.firstElementChild: any): ?HTMLElement);
    if (!rootElement) {
        return undefined;
    }

    const fiberKey = getReactFiberKey(rootElement);
    if (!fiberKey) {
        return undefined;
    }

    let currentFiber = ((rootElement: any)[fiberKey]: any);
    while (currentFiber && currentFiber.return) {
        currentFiber = currentFiber.return;
    }

    return currentFiber;
};

const getChildFibers = (fiber: any): Array<any> => {
    const children = [];
    if (!fiber || !fiber.child) {
        return children;
    }

    let child = fiber.child;
    while (child) {
        children.push(child);
        child = child.sibling;
    }

    return children;
};

const getDescendantFibers = (fiber: any, includeSelf: boolean = false): Array<any> => {
    const descendants = [];

    const traverse = (currentFiber) => {
        if (!currentFiber) {
            return;
        }

        descendants.push(currentFiber);
        getChildFibers(currentFiber).forEach(traverse);
    };

    if (includeSelf) {
        traverse(fiber);
    } else {
        getChildFibers(fiber).forEach(traverse);
    }

    return descendants;
};

const parseSelectorSegment = (segment: string): {name: string, props: {[string]: string}} => {
    const props = {};
    const propertyExpression = /\[([^=\]]+)="([^"]*)"\]/g;
    let match;

    while ((match = propertyExpression.exec(segment))) {
        props[match[1]] = match[2];
    }

    return {
        name: segment.replace(/\[[^\]]+\]/g, '').trim(),
        props,
    };
};

const parseSelector = (selector: string): Array<{combinator: 'descendant' | 'child', segment: {name: string, props: {[string]: string}}}> => {
    const tokens = selector.replace(/\s*>\s*/g, ' > ').trim().split(/\s+/).filter(Boolean);
    const parsedSegments = [];
    let combinator: 'descendant' | 'child' = 'descendant';

    tokens.forEach((token) => {
        if (token === '>') {
            combinator = 'child';
            return;
        }

        parsedSegments.push({
            combinator,
            segment: parseSelectorSegment(token),
        });
        combinator = 'descendant';
    });

    return parsedSegments;
};

const matchesSegment = (fiber: any, selectorSegment: {name: string, props: {[string]: string}}): boolean => {
    if (!fiber) {
        return false;
    }

    if (selectorSegment.name && getNodeTypeName(fiber.type) !== selectorSegment.name) {
        return false;
    }

    return Object.keys(selectorSegment.props).every((propName) => {
        const props = fiber.memoizedProps || {};
        if (propName === 'children') {
            return getChildrenText(props.children) === selectorSegment.props[propName];
        }

        return String(props[propName]) === selectorSegment.props[propName];
    });
};

const deduplicateFibers = (fibers: Array<any>): Array<any> => Array.from(new Set(fibers));

const queryFibersByStringSelector = (roots: Array<any>, selector: string): Array<any> => {
    const parsedSegments = parseSelector(selector);
    let contexts = roots;

    parsedSegments.forEach(({combinator, segment}, index) => {
        const matches = [];
        contexts.forEach((context) => {
            const candidates = combinator === 'child'
                ? getChildFibers(context)
                : getDescendantFibers(context, index === 0);

            candidates.forEach((candidate) => {
                if (matchesSegment(candidate, segment)) {
                    matches.push(candidate);
                }
            });
        });

        contexts = deduplicateFibers(matches);
    });

    return contexts;
};

const queryFibersByFunctionSelector = (roots: Array<any>, selector: Function): Array<any> => {
    const matches = [];

    roots.forEach((root) => {
        getDescendantFibers(root, true).forEach((fiber) => {
            if (fiber.type === selector) {
                matches.push(fiber);
            }
        });
    });

    return deduplicateFibers(matches);
};

const queryFibersByObjectSelector = (roots: Array<any>, selector: {[string]: any}): Array<any> => {
    const matches = [];

    roots.forEach((root) => {
        getDescendantFibers(root, true).forEach((fiber) => {
            if (typeof fiber.type === 'string') {
                return;
            }

            const props = fiber.memoizedProps || {};
            const isMatch = Object.keys(selector).every((key) => {
                if (key === 'children') {
                    return getChildrenText(props.children) === selector[key];
                }

                return props[key] === selector[key];
            });

            if (isMatch) {
                matches.push(fiber);
            }
        });
    });

    return deduplicateFibers(matches);
};

const createNodeCollection = (fibers: Array<any>): any => ({
    get length() {
        return fibers.length;
    },
    at: (index: number) => createNodeCollection(fibers[index] ? [fibers[index]] : []),
    exists: () => fibers.length > 0,
    query: (selector: any) => {
        if (typeof selector === 'string') {
            return createNodeCollection(queryFibersByStringSelector(fibers, selector));
        }

        if (typeof selector === 'function') {
            return createNodeCollection(queryFibersByFunctionSelector(fibers, selector));
        }

        return createNodeCollection(queryFibersByObjectSelector(fibers, selector));
    },
    getDOMNode: () => {
        const firstFiber = fibers[0];
        if (!firstFiber) {
            return undefined;
        }

        return firstFiber.stateNode instanceof HTMLElement ? firstFiber.stateNode : undefined;
    },
    instance: () => {
        const firstFiber = fibers[0];
        return firstFiber ? firstFiber.stateNode : undefined;
    },
    prop: (propName: string) => {
        const firstFiber = fibers[0];
        if (!firstFiber) {
            return undefined;
        }

        const props = firstFiber.memoizedProps || {};
        return props[propName];
    },
    props: () => {
        const firstFiber = fibers[0];
        return firstFiber ? (firstFiber.memoizedProps || {}) : undefined;
    },
    trigger: (eventName: string, payload?: any) => {
        const firstFiber = fibers[0];
        if (!firstFiber) {
            return;
        }

        const handlerName = eventHandlerByName[eventName] || `on${eventName.charAt(0).toUpperCase()}${eventName.slice(1)}`;
        const getFiberHandler = (fiber: any) => {
            const props = fiber.memoizedProps || {};
            const handler = props[handlerName];
            return typeof handler === 'function' ? handler : undefined;
        };

        act(() => {
            const ownHandler = getFiberHandler(firstFiber);
            if (ownHandler) {
                ownHandler(payload);
                return;
            }

            if (firstFiber.stateNode instanceof HTMLElement && fireEvent[eventName]) {
                fireEvent[eventName](firstFiber.stateNode, payload);
                return;
            }

            const descendants = getDescendantFibers(firstFiber);
            const descendantWithHandler = descendants.find((descendantFiber) => !!getFiberHandler(descendantFiber));
            if (descendantWithHandler) {
                const handler = getFiberHandler(descendantWithHandler);
                if (handler) {
                    handler(payload);
                }
                return;
            }

            const hostDescendant = descendants.find((descendantFiber) => descendantFiber.stateNode instanceof HTMLElement);
            if (hostDescendant && fireEvent[eventName]) {
                fireEvent[eventName](hostDescendant.stateNode, payload);
                return;
            }
        });
    },
});

const createWrapper = (element: React$Element<any>): any => {
    let currentProps = element.props || {};
    const renderElement = () => React.cloneElement(element, currentProps);
    const view = rtlRender(renderElement());

    const getRoot = () => {
        const rootFiber = getRootFiber(view.container);
        return rootFiber ? [rootFiber] : [];
    };

    return {
        contains: (selector: any) => {
            if (typeof selector === 'string') {
                return queryFibersByStringSelector(getRoot(), selector).length > 0;
            }

            if (typeof selector === 'function') {
                return queryFibersByFunctionSelector(getRoot(), selector).length > 0;
            }

            return queryFibersByObjectSelector(getRoot(), selector).length > 0;
        },
        query: (selector: any) => {
            if (typeof selector === 'string') {
                return createNodeCollection(queryFibersByStringSelector(getRoot(), selector));
            }

            if (typeof selector === 'function') {
                return createNodeCollection(queryFibersByFunctionSelector(getRoot(), selector));
            }

            return createNodeCollection(queryFibersByObjectSelector(getRoot(), selector));
        },
        instance: () => {
            const instances = queryFibersByFunctionSelector(getRoot(), element.type);
            return instances[0] ? instances[0].stateNode : undefined;
        },
        render: () => view.container.firstChild,
        setProps: (nextProps: Object) => {
            currentProps = {...currentProps, ...nextProps};
            act(() => {
                view.rerender(renderElement());
            });
        },
        unmount: () => view.unmount(),
        update: () => {
            act(() => {
                view.rerender(renderElement());
            });
        },
    };
};

const renderMediaCollection = (element: React$Element<any>) => createWrapper(element);
const render = (element: React$Element<any>) => {
    const view = rtlRender(element);
    const renderedOutput = view.container.firstChild;
    view.unmount();
    return renderedOutput;
};

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () =>jest.fn(function(resourceStore) {
    switch (resourceStore.resourceKey) {
        case 'collections':
            this.schema = {
                title: {
                    type: 'text_line',
                },
                description: {
                    type: 'text_line',
                },
            };
            break;
        default:
            this.schema = {};
    }

    this.data = resourceStore.data;
    this.isFieldModified = jest.fn();
    this.validate = jest.fn().mockReturnValue(true);
    this.destroy = jest.fn();
    this.types = {};
}));

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        AbstractAdapter: require('sulu-admin-bundle/containers/List/adapters/AbstractAdapter').default,
        List: require('sulu-admin-bundle/containers/List/List').default,
        ListStore: jest.fn(function(resourceKey, userSettingsKey, observableOptions) {
            const COLLECTIONS_RESOURCE_KEY = 'collections';

            const collectionData = [
                {
                    id: 1,
                    title: 'Title 1',
                    objectCount: 1,
                    description: 'Description 1',
                },
                {
                    id: 2,
                    title: 'Title 2',
                    objectCount: 0,
                    description: 'Description 2',
                },
            ];

            const thumbnails = {
                'sulu-240x': 'http://lorempixel.com/240/100',
                'sulu-100x100': 'http://lorempixel.com/100/100',
            };

            const mediaData = [
                {
                    id: 1,
                    title: 'Title 1',
                    mimeType: 'image/png',
                    size: 12345,
                    url: 'http://lorempixel.com/500/500',
                    thumbnails,
                },
                {
                    id: 2,
                    title: 'Title 1',
                    mimeType: 'image/jpeg',
                    size: 54321,
                    url: 'http://lorempixel.com/500/500',
                    thumbnails,
                },
            ];

            this.userSettingsKey = userSettingsKey;
            this.observableOptions = observableOptions;
            this.loading = false;
            this.pageCount = 3;
            this.filterOptions = {
                get: jest.fn().mockReturnValue({}),
            };
            this.active = {
                get: jest.fn(),
            };
            this.sortColumn = {
                get: jest.fn(),
            };
            this.sortOrder = {
                get: jest.fn(),
            };
            this.searchTerm = {
                get: jest.fn(),
            };
            this.limit = {
                get: jest.fn().mockReturnValue(10),
            };
            this.reset = jest.fn();
            this.reload = jest.fn();
            this.setLimit = jest.fn();
            this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
                ? collectionData
                : mediaData;
            this.selections = [];
            this.selectionIds = [];
            this.getPage = jest.fn().mockReturnValue(2);
            this.getSchema = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.updateLoadingStrategy = jest.fn();
            this.updateStructureStrategy = jest.fn();
        }),
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/List/structureStrategies/FlatStructureStrategy'
        ).default,
        Form: require('sulu-admin-bundle/containers/Form').default,
        resourceFormStoreFactory: require('sulu-admin-bundle/containers/Form/stores/resourceFormStoreFactory').default,
        memoryFormStoreFactory: {
            createFromFormKey: jest.fn(() => ({
                data: {},
                destroy: jest.fn(),
            })),
        },
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/List/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
        SingleListOverlay: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/containers/Form/registries/fieldRegistry', () => ({
    get: jest.fn().mockReturnValue(jest.fn().mockReturnValue(null)),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/containers/List/registries/listAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn(),
    };
});

jest.mock('sulu-admin-bundle/stores', () => {
    const ResourceStoreMock = jest.fn(function(resourceKey) {
        this.resourceKey = resourceKey;
        this.destroy = jest.fn();
        this.delete = jest.fn();
        this.move = jest.fn();
        this.clone = jest.fn(() => {
            // $FlowFixMe
            const resourceStore = new ResourceStoreMock(resourceKey);
            resourceStore.data = this.data;
            return resourceStore;
        });
        this.save = jest.fn();
        this.set = jest.fn();
        this.setMultiple = jest.fn();
        this.changeSchema = jest.fn();
        this.load = jest.fn();
        this.reload = jest.fn();
        this.id = 1;

        mockExtendObservable(this, {
            data: {
                id: 1,
                _permissions: {},
            },
            deleting: false,
            moving: false,
            loading: false,
        });
    });

    return {
        ResourceStore: ResourceStoreMock,
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));

beforeEach(() => {
    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;
    MediaCollection.securable = true;

    const listAdapterRegistry = require('sulu-admin-bundle/containers/List/registries/listAdapterRegistry');

    // $FlowFixMe
    listAdapterRegistry.has.mockReturnValue(true);
    // $FlowFixMe
    listAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/List/adapters/FolderAdapter').default,
        'media_card_overview': MediaCardOverviewAdapter,
    });
});

afterEach(() => {
    const body = document.body;
    if (body) {
        body.innerHTML = '';
    }
});

test('Render the MediaCollection', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const mediaCollection = render(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );
    expect(mediaCollection).toMatchSnapshot();
});

test('Render the MediaCollection without dropdown button when collection is a system collection', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    collectionStore.resourceStore.data = {
        title: 'Title',
        locked: true,
        _permissions: {},
    };

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    expect(mediaCollection.query('Button[icon="su-plus"]')).toHaveLength(0);
    expect(mediaCollection.query('DropdownButton')).toHaveLength(0);
});

test('Render the MediaCollection without dropdown button when permissions are missing', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = false;
    MediaCollection.deletable = false;
    MediaCollection.editable = false;
    MediaCollection.securable = false;

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    expect(mediaCollection.query('Button[icon="su-plus"]')).toHaveLength(0);
    expect(mediaCollection.query('DropdownButton')).toHaveLength(0);
});

test('Render the MediaCollection without add button when permission is missing', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;
    MediaCollection.securable = true;

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('DropdownButton').trigger('click');

    expect(mediaCollection.query('Button[icon="su-plus"]')).toHaveLength(0);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.delete'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.edit'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.move'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_security.permissions'})).toHaveLength(1);
});

test('Render the MediaCollection without delete button when permission is missing', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = true;
    MediaCollection.deletable = false;
    MediaCollection.editable = true;
    MediaCollection.securable = true;

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('DropdownButton').trigger('click');

    expect(mediaCollection.query('Button[icon="su-plus"]')).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.delete'})).toHaveLength(0);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.edit'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.move'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_security.permissions'})).toHaveLength(1);
});

test('Render the MediaCollection without edit buttons when permission is missing', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = false;
    MediaCollection.securable = true;

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('DropdownButton').trigger('click');

    expect(mediaCollection.query('Button[icon="su-plus"]')).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.delete'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.edit'})).toHaveLength(0);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.move'})).toHaveLength(0);
    expect(mediaCollection.query('Action').query({children: 'sulu_security.permissions'})).toHaveLength(1);
});

test('Render the MediaCollection without security buttons when permission is missing', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;
    MediaCollection.securable = false;

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('DropdownButton').trigger('click');

    expect(mediaCollection.query('Button[icon="su-plus"]')).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.delete'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.edit'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_admin.move'})).toHaveLength(1);
    expect(mediaCollection.query('Action').query({children: 'sulu_security.permissions'})).toHaveLength(0);
});

test('Reload medias and fire onUploadError callback if an error happens while uploading a file', () => {
    const page = observable.box();
    const locale = observable.box();
    const onUploadErrorSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;

    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={jest.fn()}
            onUploadError={onUploadErrorSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    expect(onUploadErrorSpy).not.toBeCalled();

    mediaCollection.query('MultiMediaDropzone').props().onUploadError(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );

    expect(onUploadErrorSpy).toBeCalledWith(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );
    expect(mediaListStore.reload).toBeCalled();
});

test('Render the MediaCollection for all media', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(undefined, locale);
    collectionStore.resourceStore.id = undefined;

    const mediaCollection = render(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );
    expect(mediaCollection).toMatchSnapshot();
});

test('Pass correct options to SingleListOverlay for moving collections', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    const moveCollectionOverlay = mediaCollection.query(SingleListOverlay).query('[title="sulu_media.move_collection"]');
    expect(moveCollectionOverlay.prop('listKey')).toEqual('collections');
    expect(moveCollectionOverlay.prop('resourceKey')).toEqual('collections');
    expect(moveCollectionOverlay.prop('reloadOnOpen')).toEqual(true);
});

test.each([true, false])('Pass correct hasChildren "%s" option to PermissionFormOverlay', (hasChildren) => {
    const page = observable.box();
    const locale = observable.box();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    mockExtendObservable(collectionStore.resourceStore.data, {
        hasChildren,
    });

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={jest.fn()}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.update();
    expect(mediaCollection.query('PermissionFormOverlay').prop('hasChildren')).toEqual(hasChildren);
});

test('Pass action for uploading new media to media list', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const List = require('sulu-admin-bundle/containers').List;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const uploadOverlayOpenSpy = jest.fn();

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={uploadOverlayOpenSpy}
            uploadOverlayOpen={false}
        />
    );

    const mediaListActions = mediaCollection.query(List).at(1).prop('actions');
    expect(mediaListActions).toHaveLength(1);
    expect(mediaListActions[0].label).toEqual('sulu_media.upload_file');
    expect(mediaListActions[0].onClick).toEqual(uploadOverlayOpenSpy);
    expect(mediaListActions[0].disabled).toBeFalsy();

    collectionStore.resourceStore.loading = true;
    mediaCollection.update();

    expect(mediaCollection.query(List).at(1).prop('actions')[0].disabled).toBeTruthy();
});

test('Do not pass action for uploading new media to media list if hideUploadAction prop is set to true', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const List = require('sulu-admin-bundle/containers').List;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    const uploadOverlayOpenSpy = jest.fn();

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            hideUploadAction={false}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={uploadOverlayOpenSpy}
            uploadOverlayOpen={false}
        />
    );

    expect(mediaCollection.query(List).at(1).prop('actions')).toHaveLength(1);

    mediaCollection.setProps({hideUploadAction: true});
    expect(mediaCollection.query(List).at(1).prop('actions')).toHaveLength(0);
});

test('Do not pass action for uploading new media to media list if addable permission is set to false', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const List = require('sulu-admin-bundle/containers').List;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    const mediaListActions = mediaCollection.query(List).at(1).prop('actions');
    expect(mediaListActions).toHaveLength(0);
});

test('Do not pass action for uploading new media to media list when collection is a system collection', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const List = require('sulu-admin-bundle/containers').List;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    collectionStore.resourceStore.data = {
        title: 'Title',
        locked: true,
        _permissions: {},
    };

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    const mediaListActions = mediaCollection.query(List).at(1).prop('actions');
    expect(mediaListActions).toHaveLength(0);
});

test('Disable dropzone if addable permission is set to false', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = false;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    expect(mediaCollection.query('MultiMediaDropzone').prop('disabled')).toBeTruthy();
});

test('Disable dropzone when collection is loading', () => {
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);

    MediaCollection.addable = true;
    MediaCollection.deletable = true;
    MediaCollection.editable = true;

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    expect(mediaCollection.query('MultiMediaDropzone').prop('disabled')).toBeFalsy();

    collectionStore.resourceStore.loading = true;
    mediaCollection.update();

    expect(mediaCollection.query('MultiMediaDropzone').prop('disabled')).toBeTruthy();
});

test('Should send a request to add a new collection via the overlay', () => {
    const fieldRegistry = require('sulu-admin-bundle/containers/Form/registries/fieldRegistry');
    const promise = Promise.resolve();
    const field = jest.fn().mockReturnValue(null);
    // $FlowFixMe
    fieldRegistry.get.mockReturnValue(field);
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.data = {
        title: 'Title',
        _permissions: {},
    };

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('Button[icon="su-plus"]').trigger('click');

    expect(collectionStore.resourceStore.clone).not.toBeCalled();
    expect(field.mock.calls[0][0].value).toEqual(undefined);

    expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
    expect(mediaCollection.query('CollectionFormOverlay > Overlay').prop('open')).toEqual(true);

    const header = document.querySelector('.content header');
    if (!header) {
        throw new Error('Header not found!');
    }
    expect(header.outerHTML).toEqual(expect.stringContaining('sulu_media.add_collection'));

    const newResourceStore = mediaCollection.query('CollectionSection').instance().resourceStoreByOperationType;
    newResourceStore.save = jest.fn().mockReturnValue(promise);

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    const button = document.querySelector('button.primary');
    if (!button) {
        throw new Error('Button not found!');
    }
    button.click();

    return promise.then(() => {
        mediaCollection.update();
        expect(mediaCollection.query('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
        expect(newResourceStore.save).toHaveBeenCalledWith({
            breadcrumb: true,
        });
        expect(newResourceStore.set).toHaveBeenCalledWith('parent', 1);
        expect(collectionNavigateSpy).toBeCalled();
        expect(collectionStore.resourceStore.setMultiple).not.toBeCalled();
    });
});

test('Should send a request to update the collection via the overlay', () => {
    const fieldRegistry = require('sulu-admin-bundle/containers/Form/registries/fieldRegistry');
    const field = jest.fn().mockReturnValue(null);
    // $FlowFixMe
    fieldRegistry.get.mockReturnValue(field);
    const promise = Promise.resolve();
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        USER_SETTINGS_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        USER_SETTINGS_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.data = {
        title: 'Title',
        _permissions: {},
    };

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('DropdownButton').trigger('click');
    mediaCollection.query('DropdownButton Action').query({children: 'sulu_admin.edit'}).trigger('click');

    // $FlowFixMe
    const resourceStoreInstances = ResourceStore.mock.instances;
    const newResourceStore = resourceStoreInstances[resourceStoreInstances.length - 1];
    newResourceStore.save.mockReturnValue(promise);
    expect(collectionStore.resourceStore.clone).toBeCalled();
    expect(field.mock.calls[0][0].value).toEqual('Title');

    expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
    expect(mediaCollection.query('CollectionFormOverlay > Overlay').prop('open')).toEqual(true);

    const header = document.querySelector('.content header');
    if (!header) {
        throw new Error('Header not found!');
    }
    expect(header.outerHTML).toEqual(expect.stringContaining('sulu_media.edit_collection'));

    // enzyme can't know about portals (rendered outside the react tree), so the document has to be used instead
    const button = document.querySelector('button.primary');
    if (!button) {
        throw new Error('Button not found!');
    }

    button.click();

    return promise.then(() => {
        mediaCollection.update();
        expect(mediaCollection.query('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
        expect(newResourceStore.save).toBeCalledWith({breadcrumb: true});
        expect(collectionNavigateSpy).not.toBeCalled();
        expect(collectionStore.resourceStore.setMultiple).toBeCalled();
    });
});

test('Confirming the delete dialog should delete the item', () => {
    const promise = Promise.resolve();
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        USER_SETTINGS_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        USER_SETTINGS_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    // $FlowFixMe
    collectionStore.resourceStore.delete = jest.fn().mockReturnValue(promise);

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('DropdownButton').trigger('click');
    mediaCollection.query('DropdownButton Action').query({children: 'sulu_admin.delete'}).trigger('click');

    expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(true);
    expect(mediaCollection.query('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);

    mediaCollection.query('Dialog Button[skin="primary"]').trigger('click');
    collectionStore.resourceStore.deleting = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.delete).toBeCalled();
    expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(true);
    expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.deleting = false;
        expect(collectionNavigateSpy).toBeCalledWith(undefined);
        mediaCollection.update();
        expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
        expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('confirmLoading'))
            .toEqual(false);
    });
});

test('Confirming the delete dialog should delete the item and navigate to its parent', () => {
    const promise = Promise.resolve();
    const page = observable.box();
    const locale = observable.box();
    const collectionNavigateSpy = jest.fn();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    // $FlowFixMe
    collectionStore.resourceStore.delete = jest.fn().mockImplementationOnce(() => {
        collectionStore.resourceStore.data = {};
        return promise;
    });

    collectionStore.resourceStore.data = {
        id: 1,
        _embedded: {
            parent: {
                id: 3,
            },
        },
        _permissions: {},
    };

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={collectionNavigateSpy}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('DropdownButton').trigger('click');
    mediaCollection.query('DropdownButton Action').query({children: 'sulu_admin.delete'}).trigger('click');
    mediaCollection.query('Dialog Button[skin="primary"]').trigger('click');

    return promise.then(() => {
        expect(collectionNavigateSpy).toBeCalledWith(3);
    });
});

test('Confirming the move dialog should move the item', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    const page = observable.box();
    const locale = observable.box();
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.move = jest.fn().mockReturnValue(promise);

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={jest.fn()}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );
    const getMoveCollectionOverlay = () => {
        return mediaCollection.query(SingleListOverlay).query('[title="sulu_media.move_collection"]');
    };

    mediaCollection.query('DropdownButton').trigger('click');
    mediaCollection.query('DropdownButton Action').query({children: 'sulu_admin.move'}).trigger('click');

    expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
    expect(mediaCollection.query('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);

    getMoveCollectionOverlay().prop('onConfirm')({id: 7});
    collectionStore.resourceStore.moving = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.move).toBeCalledWith(7);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);
    expect(getMoveCollectionOverlay().prop('options')).toEqual({includeRoot: true});
    expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.moving = false;
        mediaCollection.update();
        expect(getMoveCollectionOverlay().prop('open')).toEqual(false);
        expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(false);
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
    });
});

test('Confirming the move dialog should move the item after confirming the permission dialog', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    const page = observable.box();
    const locale = observable.box();
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.move = jest.fn().mockReturnValue(promise);

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={jest.fn()}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );
    const getMoveCollectionOverlay = () => {
        return mediaCollection.query(SingleListOverlay).query('[title="sulu_media.move_collection"]');
    };

    mediaCollection.query('DropdownButton').trigger('click');
    mediaCollection.query('DropdownButton Action').query({children: 'sulu_admin.move'}).trigger('click');

    expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
    expect(mediaCollection.query('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);

    expect(
        mediaCollection.query('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(false);
    getMoveCollectionOverlay().prop('onConfirm')({id: 7, _hasPermissions: true});
    mediaCollection.update();
    expect(
        mediaCollection.query('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(true);

    mediaCollection.query('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
        .prop('onConfirm')();

    collectionStore.resourceStore.moving = true;
    mediaCollection.update();

    expect(collectionStore.resourceStore.move).toBeCalledWith(7);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);
    expect(getMoveCollectionOverlay().prop('options')).toEqual({includeRoot: true});
    expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(true);

    return promise.then(() => {
        collectionStore.resourceStore.moving = false;
        mediaCollection.update();
        expect(getMoveCollectionOverlay().prop('open')).toEqual(false);
        expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(false);
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
    });
});

test('Confirming the move dialog should not move the item after denying the permission dialog', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    const page = observable.box();
    const locale = observable.box();
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.move = jest.fn().mockReturnValue(promise);

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={jest.fn()}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );
    const getMoveCollectionOverlay = () => {
        return mediaCollection.query(SingleListOverlay).query('[title="sulu_media.move_collection"]');
    };

    mediaCollection.query('DropdownButton').trigger('click');
    mediaCollection.query('DropdownButton Action').query({children: 'sulu_admin.move'}).trigger('click');

    expect(mediaCollection.query('Dialog[title="sulu_media.remove_collection"]').prop('open')).toEqual(false);
    expect(mediaCollection.query('CollectionFormOverlay > Overlay').prop('open')).toEqual(false);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);

    expect(
        mediaCollection.query('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(false);
    getMoveCollectionOverlay().prop('onConfirm')({id: 7, _hasPermissions: true});
    mediaCollection.update();
    expect(
        mediaCollection.query('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(true);

    mediaCollection.query('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
        .prop('onCancel')();

    mediaCollection.update();

    expect(
        mediaCollection.query('CollectionSection > div > Dialog[title="sulu_security.move_permission_title"]')
            .prop('open')
    ).toEqual(false);
    expect(getMoveCollectionOverlay().prop('open')).toEqual(true);
    expect(getMoveCollectionOverlay().prop('confirmLoading')).toEqual(false);
});

test('Confirming the permission overlay should save the permissions', () => {
    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    const page = observable.box();
    const locale = observable.box();
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const mediaListStore = new ListStore(
        MEDIA_RESOURCE_KEY,
        MEDIA_RESOURCE_KEY,
        SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const collectionListStore = new ListStore(
        COLLECTIONS_RESOURCE_KEY,
        SETTINGS_KEY,
        USER_SETTINGS_KEY,
        {
            page,
            locale,
        }
    );
    const CollectionStore = require('../../../stores/CollectionStore').default;
    const collectionStore = new CollectionStore(1, locale);
    collectionStore.resourceStore.move = jest.fn().mockReturnValue(promise);

    const mediaCollection = renderMediaCollection(
        <MediaCollection
            collectionListStore={collectionListStore}
            collectionStore={collectionStore}
            locale={locale}
            mediaListAdapters={['media_card_overview']}
            mediaListStore={mediaListStore}
            onCollectionNavigate={jest.fn()}
            onUploadOverlayClose={jest.fn()}
            onUploadOverlayOpen={jest.fn()}
            uploadOverlayOpen={false}
        />
    );

    mediaCollection.query('DropdownButton').trigger('click');
    expect(mediaCollection.query('PermissionFormOverlay').prop('open')).toEqual(false);
    mediaCollection.query('DropdownButton Action').query({children: 'sulu_security.permissions'}).trigger('click');
    expect(mediaCollection.query('PermissionFormOverlay').prop('open')).toEqual(true);

    const savePromise = Promise.resolve();
    mediaCollection.query('PermissionFormOverlay').instance().resourceStore.save.mockReturnValue(savePromise);

    mediaCollection.query('PermissionFormOverlay Form').at(0).prop('onSubmit')();

    expect(mediaCollection.query('PermissionFormOverlay').instance().resourceStore.save)
        .toBeCalledWith({resourceKey: 'media'});

    return savePromise.then(() => {
        expect(collectionStore.resourceStore.reload).toBeCalledWith();
        mediaCollection.update();
        expect(mediaCollection.query('PermissionFormOverlay').prop('open')).toEqual(false);
    });
});
