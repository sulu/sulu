// @flow
import SingleSelectBlockPreviewTransformer from '../../blockPreviewTransformers/SingleSelectBlockPreviewTransformer';

test('Return JSX for selected item', () => {
    const singleSelectBlockPreviewTransformer = new SingleSelectBlockPreviewTransformer();
    expect(
        singleSelectBlockPreviewTransformer.transform(
            'value1',
            {
                options: {
                    values: {
                        name: 'values',
                        value: [
                            {
                                name: 'value1',
                                title: 'Value 1',
                            },
                            {
                                name: 'value2',
                                title: 'Value 2',
                            },
                            {
                                name: 'value3',
                                title: 'Value 3',
                            },
                        ],
                    },
                },
                type: 'single_select',
            }
        )
    ).toMatchSnapshot();
});

test('Return null if nothing is passed', () => {
    const singleSelectBlockPreviewTransformer = new SingleSelectBlockPreviewTransformer();
    expect(
        singleSelectBlockPreviewTransformer.transform(
            undefined,
            {
                options: {
                    values: {
                        name: 'values',
                        value: [
                            {
                                name: 'value1',
                                title: 'Value 1',
                            },
                            {
                                name: 'value2',
                                title: 'Value 2',
                            },
                            {
                                name: 'value3',
                                title: 'Value 3',
                            },
                        ],
                    },
                },
                type: 'single_select',
            }
        )
    ).toMatchSnapshot();
});

test('Throw an error if schema has no values schema option', () => {
    const singleSelectBlockPreviewTransformer = new SingleSelectBlockPreviewTransformer();
    expect(
        () => singleSelectBlockPreviewTransformer.transform('value1', {type: 'single_select'})
    ).toThrow(/"values" schema option/);
});

test('Throw an error if values schema option is not an array', () => {
    const singleSelectBlockPreviewTransformer = new SingleSelectBlockPreviewTransformer();
    expect(
        () => singleSelectBlockPreviewTransformer.transform(
            'value3',
            {
                options: {
                    values: {
                        name: 'values',
                        value: 'not-array',
                    },
                },
                type: 'single_select',
            }
        )
    ).toThrow(/"values" option defined being an array/);
});
