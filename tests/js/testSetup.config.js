// @flow
import 'core-js/features/string/replace-all';
import {isObservableArray, toJS} from 'mobx';
import '@testing-library/jest-dom';

function mobxAwareEqualityTester(a, b, customTesters) {
    const isAObservable = isObservableArray(a);
    const isBObservable = isObservableArray(b);

    if (!isAObservable && !isBObservable) {
        return undefined;
    }

    const normalizedA = isAObservable ? toJS(a) : a;
    const normalizedB = isBObservable ? toJS(b) : b;

    return this.equals(normalizedA, normalizedB, customTesters);
}

expect.addEqualityTesters([mobxAwareEqualityTester]);

jest.mock('sulu-admin-bundle/services/Config', () => ({
    endpoints: {
        'config': 'config_url',
        'translations': 'translations_url',
        'loginCheck': 'login_check_url',
        'logout': 'logout_url',
        'profileSettings': 'profile_settings_url',
        'forgotPasswordReset': 'forgot_password_reset_url',
        'resetPassword': 'reset_password',
        'resources': 'resources_url/:resource',
        'routing': 'routing',
    },
    translations: ['en', 'de'],
    fallbackLocale: 'en',
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    clearTranslations: jest.fn(),
    setTranslations: jest.fn(),
    translate: jest.fn((key) => key),
}));

jest.mock('debounce', () => jest.fn((callback) => {
    const debounced = (...args) => callback(...args);
    debounced.clear = jest.fn();

    return debounced;
}));

jest.mock('loglevel', () => ({
    debug: jest.fn(),
    error: jest.fn(),
    info: jest.fn(),
    warn: jest.fn(),
}));

Object.defineProperty(window, 'matchMedia', { // see https://github.com/ckeditor/ckeditor5/issues/16368
    writable: true,
    value: jest.fn().mockImplementation((query) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: jest.fn(), // deprecated
        removeListener: jest.fn(), // deprecated
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
    })),
});

Object.defineProperty(window, 'ResizeObserver', {
    writable: true,
    value: jest.fn(function(callback) {
        const resizeObserver = (this: any);
        resizeObserver.observe = jest.fn();
        resizeObserver.disconnect = jest.fn();
        resizeObserver.callback = callback;
    }),
});

function mockCreateCKEditorView() {
    return class CKEditorViewMock {
        bindTemplate: Object;
        children: Object;
        delegate: () => Object;
        extendTemplate: () => void;
        locale: mixed;
        on: () => void;
        set: (key: string | Object, value?: mixed) => void;
        setTemplate: (template: Object) => void;
        template: Object;

        constructor(locale?: mixed) {
            this.locale = locale;
            this.template = {eventListeners: {}};
            this.children = this.createCollection();
            this.bindTemplate = {
                to: jest.fn(),
            };
            this.delegate = jest.fn(() => ({to: jest.fn()}));
            this.extendTemplate = jest.fn();
            this.on = jest.fn();
            this.set = jest.fn((key, value) => {
                if (typeof key === 'object') {
                    Object.assign(this, key);
                    return;
                }

                Object.assign(this, {[key]: value});
            });
            this.setTemplate = jest.fn((template) => this.template = template);
        }

        bind() {
            return {to: jest.fn()};
        }

        createCollection() {
            return {add: jest.fn()};
        }
    };
}

const mockCKEditorView = mockCreateCKEditorView();
const mockCKEditorButtonView = mockCreateCKEditorView();
const mockCKEditorListView = mockCreateCKEditorView();
const mockCKEditorListItemView = mockCreateCKEditorView();

class MockCKEditorPlugin {
    editor: Object;

    constructor(editor: Object = {}) {
        this.editor = editor;
    }

    listenTo() {
    }
}

class MockCKEditorCommand {
    editor: Object;

    constructor(editor: Object = {}) {
        this.editor = editor;
    }

    set(key: string, value: mixed) {
        Object.assign(this, {[key]: value});
    }
}

class MockContextualBalloon {}
class MockClickObserver {}

jest.doMock('@ckeditor/ckeditor5-core/src/plugin', () => ({
    __esModule: true,
    default: MockCKEditorPlugin,
    Plugin: MockCKEditorPlugin,
}));

jest.doMock('@ckeditor/ckeditor5-core/src/command', () => ({
    __esModule: true,
    Command: MockCKEditorCommand,
    default: MockCKEditorCommand,
}));

jest.doMock('@ckeditor/ckeditor5-ui/src/view', () => ({
    __esModule: true,
    default: mockCKEditorView,
    View: mockCKEditorView,
}));

jest.doMock('@ckeditor/ckeditor5-ui/src/button/buttonview', () => ({
    __esModule: true,
    ButtonView: mockCKEditorButtonView,
    default: mockCKEditorButtonView,
}));

jest.doMock('@ckeditor/ckeditor5-ui/src/list/listview', () => ({
    __esModule: true,
    default: mockCKEditorListView,
    ListView: mockCKEditorListView,
}));

jest.doMock('@ckeditor/ckeditor5-ui/src/list/listitemview', () => ({
    __esModule: true,
    default: mockCKEditorListItemView,
    ListItemView: mockCKEditorListItemView,
}));

jest.doMock('@ckeditor/ckeditor5-ui/src/dropdown/utils', () => ({
    __esModule: true,
    createDropdown: jest.fn(() => ({
        buttonView: new mockCKEditorButtonView(),
        panelView: {
            children: {
                add: jest.fn(),
            },
        },
    })),
}));

jest.doMock('@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon', () => ({
    __esModule: true,
    ContextualBalloon: MockContextualBalloon,
    default: MockContextualBalloon,
}));

jest.doMock('@ckeditor/ckeditor5-engine/src/view/observer/clickobserver', () => ({
    __esModule: true,
    ClickObserver: MockClickObserver,
    default: MockClickObserver,
}));
