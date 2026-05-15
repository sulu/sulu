// @flow

import React from 'react';
import {render, fireEvent, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import symfonyRouting from 'fos-jsrouting/router';
import {translate} from '../../../utils';
import AiApplication from '../AiApplication';
import WritingAssistant from '../../WritingAssistant';
import Translator from '../../Translator';

jest.mock('fos-jsrouting/router');
jest.mock('../../../utils');
jest.mock('../../../containers');
jest.mock('../../TextEditor/adapters/CKEditor5');
jest.mock('../../WritingAssistant', () => jest.fn(() => null));
jest.mock('../../Translator', () => jest.fn(() => null));
jest.mock('../ActionOverlay');

const getLatestMockProps = (mockComponent: Function) => {
    const calls = ((mockComponent: any).mock.calls: any);

    return calls[calls.length - 1][0];
};

const createFocusTarget = () => {
    const container = document.createElement('div');
    const input = document.createElement('input');
    Object.defineProperty((container: any), 'getBoundingClientRect', {
        value: jest.fn().mockReturnValue({
            top: 0,
            right: 0,
            bottom: 0,
            left: 0,
            width: 0,
            height: 0,
        }),
    });
    container.appendChild(input);
    if (!document.body) {
        throw new Error('Expected document body to exist');
    }

    document.body.appendChild(container);

    return {container, input};
};

const dispatchSuluFocus = (target: HTMLElement, detail: Object = {}) => {
    const event = new Event('sulu.focus');
    Object.defineProperty(event, 'target', {value: target});
    Object.defineProperty((event: any), 'detail', {
        value: {
            dataPath: 'dataPath',
            formInspector: {
                getSchemaEntryByPath: jest.fn(),
                id: '1',
                locale: {get: () => 'en'},
                options: {webspace: 'sulu'},
                resourceKey: 'pages',
            },
            getValue: jest.fn().mockReturnValue('text'),
            schemaPath: 'schemaPath',
            schemaType: 'text_line',
            setValue: jest.fn(),
            ...detail,
        },
    });
    fireEvent(document, event);
};

const openWritingAssistant = async() => {
    await userEvent.click(screen.getByTitle('sulu_admin.writing_assistant'));
};

const openTranslator = async() => {
    await userEvent.click(screen.getByTitle('sulu_admin.translator'));
};

describe('AiApplication', () => {
    let props;

    beforeEach(() => {
        jest.clearAllMocks();

        props = {
            feedback: {
                enabled: true,
                formKey: 'formKey',
                route: 'feedbackRoute',
            },
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

        symfonyRouting.generate.mockImplementation((route, params: {[string]: string} = {}) => {
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
        const {input} = createFocusTarget();

        dispatchSuluFocus(input);

        expect(screen.getByTitle('sulu_admin.translator')).toBeInTheDocument();
        expect(screen.getByTitle('sulu_admin.writing_assistant')).toBeInTheDocument();
    });

    test('handles scroll and resize events', () => {
        render(<AiApplication {...props} />);
        const {container, input} = createFocusTarget();

        dispatchSuluFocus(input);

        fireEvent.scroll(window);

        expect(container.getBoundingClientRect).toHaveBeenCalledTimes(2);

        fireEvent.resize(window);

        expect(container.getBoundingClientRect).toHaveBeenCalledTimes(3);
    });

    test('handles global click event', () => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();

        dispatchSuluFocus(input);

        const clickEvent = new Event('click');
        Object.defineProperty(clickEvent, 'target', {
            // $FlowFixMe
            value: {
                matches: jest.fn().mockReturnValue(false),
                closest: jest.fn().mockReturnValue(null),
            },
        });

        fireEvent(document, clickEvent);

        expect(screen.getByTitle('sulu_admin.translator')).toBeInTheDocument();
        expect(screen.getByTitle('sulu_admin.writing_assistant')).toBeInTheDocument();
    });

    test('handles writing assistant close', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();
        const getValue = jest.fn().mockReturnValue('text');

        dispatchSuluFocus(input, {getValue});
        await openWritingAssistant();
        getLatestMockProps(WritingAssistant).onDialogClose();

        expect(getValue).toHaveBeenCalledTimes(3);
        expect(screen.queryByTitle('sulu_admin.writing_assistant')).not.toBeInTheDocument();
    });

    test('handles writing assistant confirm', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();
        const setValue = jest.fn();

        dispatchSuluFocus(input, {setValue});
        await openWritingAssistant();
        getLatestMockProps(WritingAssistant).onConfirm('optimizedText');

        expect(setValue).toHaveBeenCalledWith('optimizedText');
        expect(screen.getByTitle('sulu_admin.writing_assistant')).toBeInTheDocument();
    });

    test('handles translate close', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();
        const getValue = jest.fn().mockReturnValue('text');

        dispatchSuluFocus(input, {getValue});
        await openTranslator();
        getLatestMockProps(Translator).onDialogClose();

        expect(getValue).toHaveBeenCalledTimes(3);
        expect(screen.queryByTitle('sulu_admin.translator')).not.toBeInTheDocument();
    });

    test('captures dataPath from sulu.focus event', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();

        dispatchSuluFocus(input, {dataPath: '/block/0/text'});
        await openWritingAssistant();

        expect(getLatestMockProps(WritingAssistant).dataPath).toBe('/block/0/text');
    });

    test('contentData computed returns form data filtered by schema', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();

        dispatchSuluFocus(input, {
            formInspector: {
                getSchemaEntryByPath: jest.fn(),
                locale: {get: () => 'en'},
                formStore: {
                    schema: {
                        title: {type: 'text_line'},
                        description: {type: 'text_area'},
                    },
                    data: {
                        title: 'My Page',
                        description: 'A description',
                        internalField: 'should not appear',
                    },
                },
            },
        });
        await openWritingAssistant();

        expect(getLatestMockProps(WritingAssistant).contentData).toEqual({
            title: 'My Page',
            description: 'A description',
        });
    });

    test('contentData computed handles nested sections', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();

        dispatchSuluFocus(input, {
            formInspector: {
                getSchemaEntryByPath: jest.fn(),
                locale: {get: () => 'en'},
                formStore: {
                    schema: {
                        details: {
                            type: 'section',
                            items: {
                                title: {type: 'text_line'},
                                summary: {type: 'text_area'},
                            },
                        },
                        metadata: {
                            type: 'section',
                            items: {
                                author: {type: 'text_line'},
                            },
                        },
                    },
                    data: {
                        title: 'Page Title',
                        summary: 'Page Summary',
                        author: 'John',
                    },
                },
            },
        });
        await openWritingAssistant();

        expect(getLatestMockProps(WritingAssistant).contentData).toEqual({
            title: 'Page Title',
            summary: 'Page Summary',
            author: 'John',
        });
    });

    test('contentData computed returns undefined when no formStore', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();

        dispatchSuluFocus(input, {
            formInspector: {
                getSchemaEntryByPath: jest.fn(),
                locale: {get: () => 'en'},
            },
        });
        await openWritingAssistant();

        expect(getLatestMockProps(WritingAssistant).contentData).toBeUndefined();
    });

    test('contentData computed returns undefined when data is empty', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();

        dispatchSuluFocus(input, {
            formInspector: {
                getSchemaEntryByPath: jest.fn(),
                locale: {get: () => 'en'},
                formStore: {
                    schema: {
                        title: {type: 'text_line'},
                    },
                    data: {},
                },
            },
        });
        await openWritingAssistant();

        expect(getLatestMockProps(WritingAssistant).contentData).toBeUndefined();
    });

    test('handles translate confirm', async() => {
        render(<AiApplication {...props} />);
        const {input} = createFocusTarget();
        const setValue = jest.fn();

        dispatchSuluFocus(input, {setValue});
        await openTranslator();
        getLatestMockProps(Translator).onConfirm('translatedText');

        expect(setValue).toHaveBeenCalledWith('translatedText');
        expect(screen.getByTitle('sulu_admin.translator')).toBeInTheDocument();
    });
});
