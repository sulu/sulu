// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import Renderer from '../Renderer';
import FormInspector from '../FormInspector';
import ResourceFormStore from '../stores/ResourceFormStore';

let mockFieldTypeProps: Array<Object> = [];

jest.mock('../../../services/Router/Router', () => jest.fn());
jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));
jest.mock('../FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
}));
jest.mock('../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());

class mockFieldType extends React.Component<Object> {
    handleChangeClick = () => {
        this.props.onChange('changed');
    };

    render() {
        const props = this.props;

        mockFieldTypeProps.push(props);

        return (
            <div
                data-data-path={props.dataPath}
                data-error-keyword={props.error && props.error.keyword}
                data-schema-path={props.schemaPath}
                data-testid={'field-' + props.label}
            >
                <input aria-label={props.label} readOnly={true} value={props.value || ''} />
                <button aria-label={'finish-' + props.label} onClick={props.onFinish} type="button">
                    Finish
                </button>
                <button aria-label={'change-' + props.label} onClick={this.handleChangeClick} type="button">
                    Change
                </button>
            </div>
        );
    }
}

jest.mock('../registries/fieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
            case 'datetime':
                return mockFieldType;
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

function createFormInspector() {
    return new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
}

function renderRenderer(props: Object = {}) {
    return render(
        <Renderer
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={undefined}
            router={undefined}
            schema={{}}
            schemaPath=""
            value={{}}
            {...props}
        />
    );
}

function getField(label: string): HTMLElement {
    return screen.getByTestId('field-' + label);
}

beforeEach(() => {
    jest.clearAllMocks();
    mockFieldTypeProps = [];
});

test('Should call onFieldFinish callback when editing a field has finished', async() => {
    const user = userEvent.setup();
    const schema = {
        text: {
            label: 'Text',
            type: 'text_line',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };
    const fieldFinishSpy = jest.fn();

    renderRenderer({
        onFieldFinish: fieldFinishSpy,
        schema,
    });

    await user.click(screen.getByLabelText('finish-Text'));
    expect(fieldFinishSpy).toHaveBeenLastCalledWith('/text', '/text');

    await user.click(screen.getByLabelText('finish-Datetime'));
    expect(fieldFinishSpy).toHaveBeenLastCalledWith('/datetime', '/datetime');
});

test('Should render field types based on schema', () => {
    renderRenderer({
        schema: {
            text: {
                label: 'Text',
                type: 'text_line',
            },
            datetime: {
                label: 'Datetime',
                type: 'datetime',
            },
        },
    });

    expect(screen.getByLabelText('Text')).toBeInTheDocument();
    expect(screen.getByLabelText('Datetime')).toBeInTheDocument();
});

test('Should render nested field types with based on schema', () => {
    renderRenderer({
        data: {
            test: {
                text: 'Text',
                datetime: 'DateTime',
            },
        },
        schema: {
            'test/text': {
                label: 'Text',
                type: 'text_line',
            },
            'test/datetime': {
                label: 'Datetime',
                type: 'datetime',
            },
        },
        value: {
            test: {
                text: 'Text',
                datetime: 'DateTime',
            },
        },
    });

    expect(screen.getByLabelText('Text')).toHaveValue('Text');
    expect(screen.getByLabelText('Datetime')).toHaveValue('DateTime');
});

test('Should not render fields when the schema contains a visibleCondition evaluating false', () => {
    renderRenderer({
        data: {test: 'Test'},
        schema: {
            highlight: {
                items: {
                    title: {
                        label: 'Title',
                        type: 'text_line',
                        visibleCondition: 'test == "Test"',
                    },
                    url: {
                        label: 'URL',
                        type: 'text_line',
                        visibleCondition: 'test == false',
                    },
                },
                label: 'Highlight',
                type: 'section',
                visibleCondition: 'test == "Test"',
            },
            highlight2: {
                items: {
                    title: {
                        label: 'Hidden title',
                        type: 'text_line',
                        visibleCondition: 'test == "Test"',
                    },
                },
                label: 'Hidden highlight',
                type: 'section',
                visibleCondition: 'test == false',
            },
            text: {
                label: 'Text',
                type: 'text_line',
            },
            datetime: {
                label: 'Datetime',
                type: 'datetime',
                visibleCondition: 'test == false',
            },
        },
        value: {test: 'Test'},
    });

    expect(screen.getByText('Highlight')).toBeInTheDocument();
    expect(screen.getByLabelText('Title')).toBeInTheDocument();
    expect(screen.getByLabelText('Text')).toBeInTheDocument();
    expect(screen.queryByLabelText('URL')).not.toBeInTheDocument();
    expect(screen.queryByText('Hidden highlight')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Datetime')).not.toBeInTheDocument();
});

test('Should pass correct dataPath and schemaPath to fields', () => {
    renderRenderer({
        dataPath: '/block/0',
        schema: {
            highlight: {
                items: {
                    title: {
                        label: 'Title',
                        type: 'text_line',
                    },
                    url: {
                        label: 'URL',
                        type: 'text_line',
                    },
                },
                type: 'section',
            },
            article: {
                label: 'Article',
                type: 'text_line',
            },
        },
        schemaPath: '/test',
    });

    expect(getField('Title')).toHaveAttribute('data-schema-path', '/test/highlight/items/title');
    expect(getField('Title')).toHaveAttribute('data-data-path', '/block/0/title');
    expect(getField('URL')).toHaveAttribute('data-schema-path', '/test/highlight/items/url');
    expect(getField('URL')).toHaveAttribute('data-data-path', '/block/0/url');
    expect(getField('Article')).toHaveAttribute('data-schema-path', '/test/article');
    expect(getField('Article')).toHaveAttribute('data-data-path', '/block/0/article');
});

test('Should pass correct data and value to fields', () => {
    const value = {
        title: 'Some title',
        url: 'some-url',
        article: 'Some article',
    };
    const data = {
        title: 'Page title',
        block: value,
    };

    renderRenderer({
        data,
        dataPath: '/block/0',
        schema: {
            highlight: {
                items: {
                    title: {
                        label: 'Title',
                        type: 'text_line',
                    },
                    url: {
                        label: 'URL',
                        type: 'text_line',
                    },
                },
                type: 'section',
            },
            article: {
                label: 'Article',
                type: 'text_line',
            },
        },
        schemaPath: '/test',
        value,
    });

    expect(mockFieldTypeProps[0].data).toEqual(data);
    expect(mockFieldTypeProps[0].value).toEqual('Some title');
    expect(mockFieldTypeProps[1].data).toEqual(data);
    expect(mockFieldTypeProps[1].value).toEqual('some-url');
    expect(mockFieldTypeProps[2].data).toEqual(data);
    expect(mockFieldTypeProps[2].value).toEqual('Some article');
});

test('Should pass formInspector, onSuccess, router and onChange to fields', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const successSpy = jest.fn();
    const fieldFinishSpy = jest.fn();
    const formInspector = createFormInspector();
    const router = new Router();

    renderRenderer({
        formInspector,
        onChange: changeSpy,
        onFieldFinish: fieldFinishSpy,
        onSuccess: successSpy,
        router,
        schema: {
            text: {
                label: 'Text',
                type: 'text_line',
            },
            datetime: {
                label: 'Datetime',
                type: 'datetime',
            },
        },
    });

    expect(mockFieldTypeProps[0].formInspector).toBe(formInspector);
    expect(mockFieldTypeProps[0].router).toBe(router);
    expect(mockFieldTypeProps[1].onSuccess).toBe(successSpy);
    expect(mockFieldTypeProps[1].router).toBe(router);

    await user.click(screen.getByLabelText('change-Text'));
    expect(changeSpy).toHaveBeenCalledWith('text', 'changed', undefined);
});

test('Should pass errors to fields that have already been modified at least once', () => {
    const textError = {
        keyword: 'required',
        parameters: {},
    };
    const datetimeError = {
        keyword: 'minLength',
        parameters: {},
    };
    const formInspector = createFormInspector();
    formInspector.isFieldModified.mockImplementation((dataPath) => {
        return dataPath === '/text' ? true : false;
    });

    renderRenderer({
        errors: {
            text: textError,
            datetime: datetimeError,
        },
        formInspector,
        schema: {
            text: {
                label: 'Text',
                type: 'text_line',
            },
            datetime: {
                label: 'Datetime',
                type: 'datetime',
            },
        },
    });

    expect(mockFieldTypeProps[0].error).toBe(textError);
    expect(mockFieldTypeProps[1].error).toBe(undefined);
});

test('Should pass all errors to fields if showAllErrors is set to true', () => {
    const textError = {
        keyword: 'required',
        parameters: {},
    };
    const datetimeError = {
        keyword: 'minLength',
        parameters: {},
    };

    renderRenderer({
        errors: {
            text: textError,
            datetime: datetimeError,
        },
        schema: {
            text: {
                label: 'Text',
                type: 'text_line',
            },
            datetime: {
                label: 'Datetime',
                type: 'datetime',
            },
        },
        showAllErrors: true,
    });

    expect(mockFieldTypeProps[0].error).toBe(textError);
    expect(mockFieldTypeProps[1].error).toBe(datetimeError);
});

test('Should render nested sections', () => {
    renderRenderer({
        schema: {
            section1: {
                label: 'Section 1',
                type: 'section',
                items: {
                    item11: {
                        label: 'Item 1.1',
                        type: 'text_line',
                    },
                    section11: {
                        label: 'Section 1.1',
                        type: 'section',
                    },
                },
            },
            section2: {
                label: 'Section 2',
                type: 'section',
                items: {
                    item21: {
                        label: 'Item 2.1',
                        type: 'text_line',
                    },
                },
            },
        },
    });

    expect(screen.getByText('Section 1')).toBeInTheDocument();
    expect(screen.getByText('Section 1.1')).toBeInTheDocument();
    expect(screen.getByText('Section 2')).toBeInTheDocument();
    expect(screen.getByLabelText('Item 1.1')).toBeInTheDocument();
    expect(screen.getByLabelText('Item 2.1')).toBeInTheDocument();
});

test('Should render sections with colSpan', () => {
    renderRenderer({
        schema: {
            section1: {
                label: 'Section 1',
                type: 'section',
                colSpan: 8,
                items: {
                    item11: {
                        label: 'Item 1.1',
                        type: 'text_line',
                    },
                },
            },
            section2: {
                label: 'Section 2',
                type: 'section',
                colSpan: 4,
                items: {
                    item21: {
                        label: 'Item 2.1',
                        type: 'text_line',
                    },
                },
            },
        },
    });

    expect(screen.getByText('Section 1').closest('.gridSection')).toHaveClass('colSpan-8');
    expect(screen.getByText('Section 2').closest('.gridSection')).toHaveClass('colSpan-4');
});

test('Should render sections without label', () => {
    renderRenderer({
        schema: {
            section1: {
                type: 'section',
                colSpan: 8,
                items: {
                    item11: {
                        label: 'Item 1.1',
                        type: 'text_line',
                    },
                },
            },
            section2: {
                label: 'Section 2',
                type: 'section',
                colSpan: 4,
                items: {
                    item21: {
                        label: 'Item 2.1',
                        type: 'text_line',
                    },
                },
            },
        },
    });

    expect(screen.getByLabelText('Item 1.1')).toBeInTheDocument();
    expect(screen.getByText('Section 2')).toBeInTheDocument();
});
