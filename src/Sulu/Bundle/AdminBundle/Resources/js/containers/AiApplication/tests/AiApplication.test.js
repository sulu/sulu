// @flow

import React from 'react';
import {render, fireEvent, screen} from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import symfonyRouting from 'fos-jsrouting/router';
import {translate} from '../../../utils';
import AiApplication from '../AiApplication';

jest.mock('fos-jsrouting/router');
jest.mock('../../../utils');
jest.mock('../../../containers');
jest.mock('../../TextEditor/adapters/CKEditor5');
jest.mock('../../WritingAssistant');
jest.mock('../../Translator');
jest.mock('../ActionOverlay');

describe('AiApplication', () => {
    let props;

    beforeEach(() => {
        props = {
            feedback: {
                enabled: true,
                formKey: 'formKey',
                route: 'feedbackRoute',
            },
            htmlFieldTypes: ['text_editor'],
            textFieldTypes: ['text_line', 'text_area'],
            translation: {
                enabled: true,
                route: 'translationRoute',
                sourceLanguages: [{label: 'English', locale: 'en'}],
                targetLanguages: [{label: 'French', locale: 'fr'}],
            },
            writingAssistant: {
                enabled: true,
                experts: {},
                route: 'writingAssistantRoute',
            },
        };

        symfonyRouting.generate.mockImplementation((route, params: {[string]: string}) => {
            // $FlowFixMe
            return `${route}?${Object.entries(params).map(([key, value: string]) => `${key}=${value}`).join('&')}`;
        });

        // $FlowFixMe
        translate.mockImplementation((key) => key);
    });

    test('renders without crashing initially', () => {
        const {container} = render(<AiApplication {...props} />);

        expect(container).toBeEmptyDOMElement();
    });

    test('renders FeatureBadge when hasFocus is true', () => {
        render(<AiApplication {...props} />);

        // Create a mock HTMLElement
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        // Create a mock event object
        const event = new Event('sulu.focus');
        Object.defineProperty(event, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(event, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });

        // Simulate the sulu.focus event to set hasFocus to true
        fireEvent(document, event);

        // Now, hasFocus should be true and FeatureBadge should be rendered
        expect(screen.getByTitle('sulu_admin.translator')).toBeInTheDocument();
        expect(screen.getByTitle('sulu_admin.writing_assistant')).toBeInTheDocument();
    });

    test('does not render for an unknown schemaType by default', () => {
        const {container} = render(<AiApplication {...props} />);

        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0, right: 0, bottom: 0, left: 0, width: 0, height: 0,
                }),
            },
        });

        const event = new Event('sulu.focus');
        Object.defineProperty(event, 'target', {value: mockElement});
        // $FlowFixMe
        Object.defineProperty(event, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'configurable_text_editor',
                setValue: jest.fn(),
            },
        });

        fireEvent(document, event);

        expect(container).toBeEmptyDOMElement();
    });

    test('renders FeatureBadge for custom htmlFieldTypes', () => {
        render(
            <AiApplication
                {...props}
                htmlFieldTypes={['text_editor', 'configurable_text_editor']}
            />
        );

        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0, right: 0, bottom: 0, left: 0, width: 0, height: 0,
                }),
            },
        });

        const event = new Event('sulu.focus');
        Object.defineProperty(event, 'target', {value: mockElement});
        // $FlowFixMe
        Object.defineProperty(event, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'configurable_text_editor',
                setValue: jest.fn(),
            },
        });

        fireEvent(document, event);

        expect(screen.getByTitle('sulu_admin.translator')).toBeInTheDocument();
        expect(screen.getByTitle('sulu_admin.writing_assistant')).toBeInTheDocument();
    });

    test('canonicalFieldType classifies custom html types as text_editor', () => {
        const instance = new AiApplication({
            ...props,
            htmlFieldTypes: ['text_editor', 'configurable_text_editor'],
            textFieldTypes: ['text_line', 'text_area', 'custom_line'],
        });

        expect(instance.canonicalFieldType('configurable_text_editor')).toBe('text_editor');
        expect(instance.canonicalFieldType('text_editor')).toBe('text_editor');
        expect(instance.canonicalFieldType('text_area')).toBe('text_area');
        expect(instance.canonicalFieldType('custom_line')).toBe('text_line');
        expect(instance.canonicalFieldType('text_line')).toBe('text_line');
    });

    test('canonicalFieldType maps canonical types to themselves regardless of configuration', () => {
        const instance = new AiApplication({
            ...props,
            htmlFieldTypes: ['configurable_text_editor'],
            textFieldTypes: ['custom_line'],
        });

        expect(instance.canonicalFieldType('text_editor')).toBe('text_editor');
        expect(instance.canonicalFieldType('text_area')).toBe('text_area');
        expect(instance.canonicalFieldType('text_line')).toBe('text_line');
    });

    test('handles scroll and resize events', () => {
        render(<AiApplication {...props} />);

        // Create a mock HTMLElement
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const event = new Event('sulu.focus');
        Object.defineProperty(event, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(event, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, event);

        // Simulate scroll event
        fireEvent.scroll(window);

        // Check if getBoundingClientRect was called
        // $FlowFixMe
        expect(mockElement.parentElement.getBoundingClientRect).toHaveBeenCalledTimes(2);

        // Simulate resize event
        fireEvent.resize(window);

        // Check if getBoundingClientRect was called again
        // $FlowFixMe
        expect(mockElement.parentElement.getBoundingClientRect).toHaveBeenCalledTimes(3);
    });

    test('handles global click event', () => {
        render(<AiApplication {...props} />);

        // Create a mock HTMLElement
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Create a mock click event
        const clickEvent = new Event('click');
        Object.defineProperty(clickEvent, 'target', {
            // $FlowFixMe
            value: {
                matches: jest.fn().mockReturnValue(false),
                closest: jest.fn().mockReturnValue(null),
            },
        });

        // Simulate the global click event
        fireEvent(document, clickEvent);

        // Check if hasFocus is set to false
        expect(screen.getByTitle('sulu_admin.translator')).toBeInTheDocument();
        expect(screen.getByTitle('sulu_admin.writing_assistant')).toBeInTheDocument();
    });

    test('handles writing assistant close', () => {
        render(<AiApplication {...props} />);

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn().mockReturnValue('text'),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Simulate the writing assistant close action
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {getValue: jest.fn().mockReturnValue('text')};
        instance.handleWritingAssistantClose();

        // Check the state changes
        expect(instance.selectedText).toBe('text');
        expect(instance.writingAssistantOpen).toBe(false);
        expect(instance.hasFocus).toBe(false);
    });

    test('handles writing assistant confirm', () => {
        render(<AiApplication {...props} />);

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Simulate the writing assistant confirm action
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {setValue: jest.fn()};
        // $FlowFixMe
        instance.selectedElement = {
            focus: jest.fn(),
            selectionStart: 0,
            selectionEnd: 0,
            value: {length: 0},
        };
        instance.handleWritingAssistantConfirm('optimizedText');

        // Check the state changes
        expect(instance.selectedComponent.setValue).toHaveBeenCalledWith('optimizedText');
        expect(instance.writingAssistantOpen).toBe(false);
        expect(instance.hasFocus).toBe(true);
    });

    test('handles translate close', () => {
        render(<AiApplication {...props} />);

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn().mockReturnValue('text'),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Simulate the translate close action
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {getValue: jest.fn().mockReturnValue('text')};
        instance.handleTranslateClose();

        // Check the state changes
        expect(instance.selectedText).toBe('text');
        expect(instance.translateOpen).toBe(false);
        expect(instance.hasFocus).toBe(false);
    });

    test('isInsideBlock returns false when schema lookup throws on a path segment', () => {
        const instance = new AiApplication(props);

        const formInspector = {
            getSchemaEntryByPath: jest.fn().mockImplementation(() => {
                throw new Error('Invalid reference token: excerpt');
            }),
        };

        // $FlowFixMe - only the methods used by isInsideBlock are relevant here
        const result = instance.isInsideBlock(formInspector, '/excerpt/description');

        expect(result).toBe(false);
        expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/excerpt');
    });

    test('isInsideBlock returns true when any ancestor in the schema path is a block', () => {
        const instance = new AiApplication(props);

        const formInspector = {
            getSchemaEntryByPath: jest.fn().mockImplementation((path) => {
                if (path === '/content') {
                    return {type: 'block'};
                }

                return {type: 'text_line'};
            }),
        };

        // $FlowFixMe - only the methods used by isInsideBlock are relevant here
        const result = instance.isInsideBlock(formInspector, '/content/0/title');

        expect(result).toBe(true);
    });

    test('isInsideBlock returns false when no ancestor is a block', () => {
        const instance = new AiApplication(props);

        const formInspector = {
            getSchemaEntryByPath: jest.fn().mockReturnValue({type: 'section'}),
        };

        // $FlowFixMe - only the methods used by isInsideBlock are relevant here
        const result = instance.isInsideBlock(formInspector, '/details/title');

        expect(result).toBe(false);
    });

    test('captures dataPath from sulu.focus event', () => {
        const instance = new AiApplication(props);

        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0, right: 0, bottom: 0, left: 0, width: 0, height: 0,
                }),
            },
        });

        const event = new Event('sulu.focus');
        Object.defineProperty(event, 'target', {value: mockElement});
        // $FlowFixMe
        Object.defineProperty(event, 'detail', {
            value: {
                dataPath: '/block/0/text',
                formInspector: {locale: {get: () => 'en'}, getSchemaEntryByPath: jest.fn()},
                getValue: jest.fn().mockReturnValue('some text'),
                schemaPath: 'title',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });

        instance.handleSuluFocus(event);

        expect(instance.selectedComponent.dataPath).toBe('/block/0/text');
    });

    test('contentData computed leaves out what the resource records about itself', () => {
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {
            // $FlowFixMe
            formInspector: {
                formStore: {
                    data: {
                        title: 'My Page',
                        description: 'A description',
                        customField: 'an unknown field counts as content',
                        id: 'page-uuid',
                        template: 'default',
                        webspace: 'website',
                        created: '2026-07-29T08:00:00+00:00',
                        changer: null,
                        _permissions: {view: true},
                        _hash: 'abc',
                    },
                },
            },
        };

        const result = instance.contentData;

        expect(result).toEqual({
            title: 'My Page',
            description: 'A description',
            customField: 'an unknown field counts as content',
        });
    });

    test('contentData computed offers content the open form does not describe', () => {
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {
            // $FlowFixMe
            formInspector: {
                formStore: {
                    // the seo tab describes only its own fields, yet writing seo text needs the page content
                    schema: {
                        'ext/seo/title': {type: 'text_line'},
                        'ext/seo/description': {type: 'text_area'},
                    },
                    data: {
                        title: 'Page Title',
                        article: 'Page Body',
                        ext: {seo: {title: '', description: ''}},
                        id: 'page-uuid',
                    },
                },
            },
        };

        const result = instance.contentData;

        expect(result).toEqual({
            title: 'Page Title',
            article: 'Page Body',
            ext: {seo: {title: '', description: ''}},
        });
    });

    test('contentData computed returns undefined when no formStore', () => {
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {
            // $FlowFixMe
            formInspector: {},
        };

        expect(instance.contentData).toBeUndefined();
    });

    test('contentData computed returns undefined when data is empty', () => {
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {
            // $FlowFixMe
            formInspector: {
                formStore: {
                    schema: {
                        title: {type: 'text_line'},
                    },
                    data: {},
                },
            },
        };

        expect(instance.contentData).toBeUndefined();
    });

    test('handles translate confirm', () => {
        render(<AiApplication {...props} />);

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Simulate the translate confirm action
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {setValue: jest.fn()};
        // $FlowFixMe
        instance.selectedElement = {
            focus: jest.fn(),
            selectionStart: 0,
            selectionEnd: 0,
            value: {length: 0},
        };
        instance.handleTranslateConfirm('translatedText');

        // Check the state changes
        expect(instance.selectedComponent.setValue).toHaveBeenCalledWith('translatedText');
        expect(instance.translateOpen).toBe(false);
        expect(instance.hasFocus).toBe(true);
    });
});
