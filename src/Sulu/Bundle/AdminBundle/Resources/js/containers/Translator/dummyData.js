// src/Sulu/Bundle/AdminBundle/Resources/js/containers/Translator/dummyData.js
// @flow

// Pre-defined segments for demonstration
export const DUMMY_SEGMENTS = [
    {
        id: 1,
        text: "Welcome to our website.",
        beginPos: 0,
        endPos: 23
    },
    {
        id: 2,
        text: "We offer premium services for all your needs.",
        beginPos: 24,
        endPos: 69
    },
    {
        id: 3,
        text: "Contact us for more information.",
        beginPos: 70,
        endPos: 104
    }
];

// Pre-defined alternatives for demonstration
export const DUMMY_ALTERNATIVES = {
    1: [
        "Welcome to our website.",
        "Welcome to our site.",
        "Thanks for visiting our website.",
        "We're glad you're here at our website.",
        "Hello and welcome to our website."
    ],
    2: [
        "We offer premium services for all your needs.",
        "We provide high-quality services for your requirements.",
        "Our premium services fulfill all your needs.",
        "All your needs can be met by our premium services.",
        "For all your needs, we offer top-notch services."
    ],
    3: [
        "Contact us for more information.",
        "Get in touch for further details.",
        "Reach out to us for additional information.",
        "For more details, please contact us.",
        "Need more info? Contact our team."
    ]
};
