import React, { useEffect } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import register from '@/api/auth/register';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { Formik, FormikHelpers } from 'formik';
import { object, string, ref } from 'yup';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import useFlash from '@/plugins/useFlash';

interface Values {
    email: string;
    username: string;
    nameFirst: string;
    nameLast: string;
    password: string;
    passwordConfirmation: string;
}

const RegisterContainer = ({ history }: RouteComponentProps) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    useEffect(() => {
        clearFlashes();
    }, []);

    const onSubmit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();

        register(values)
            .then((response) => {
                if (response.complete) {
                    // @ts-expect-error this is valid
                    window.location = response.intended || '/';
                    return;
                }

                history.replace('/auth/login');
            })
            .catch((error) => {
                console.error(error);

                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
    };

    return (
        <Formik
            onSubmit={onSubmit}
            initialValues={{
                email: '',
                username: '',
                nameFirst: '',
                nameLast: '',
                password: '',
                passwordConfirmation: '',
            }}
            validationSchema={object().shape({
                email: string().email('Please enter a valid email address.').required('An email is required.'),
                username: string().required('A username is required.'),
                nameFirst: string().required('Your first name is required.'),
                nameLast: string().required('Your last name is required.'),
                password: string()
                    .min(8, 'Password must be at least 8 characters.')
                    .required('A password is required.'),
                passwordConfirmation: string()
                    .oneOf([ref('password'), null], 'Passwords must match.')
                    .required('Please confirm your password.'),
            })}
        >
            {({ isSubmitting, submitForm }) => (
                <LoginFormContainer
                    title={'Create an Account'}
                    css={tw`w-full flex`}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            submitForm();
                        }
                    }}
                >
                    <Field light type={'email'} label={'Email'} name={'email'} disabled={isSubmitting} />
                    <div css={tw`mt-6`}>
                        <Field light type={'text'} label={'Username'} name={'username'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-6 flex gap-4`}>
                        <div css={tw`w-1/2`}>
                            <Field
                                light
                                type={'text'}
                                label={'First Name'}
                                name={'nameFirst'}
                                disabled={isSubmitting}
                            />
                        </div>
                        <div css={tw`w-1/2`}>
                            <Field light type={'text'} label={'Last Name'} name={'nameLast'} disabled={isSubmitting} />
                        </div>
                    </div>
                    <div css={tw`mt-6`}>
                        <Field light type={'password'} label={'Password'} name={'password'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-6`}>
                        <Field
                            light
                            type={'password'}
                            label={'Confirm Password'}
                            name={'passwordConfirmation'}
                            disabled={isSubmitting}
                        />
                    </div>
                    <div css={tw`mt-6`}>
                        <Button type={'submit'} size={'xlarge'} isLoading={isSubmitting} disabled={isSubmitting}>
                            Create Account
                        </Button>
                    </div>
                    <div css={tw`mt-6 text-center`}>
                        <Link
                            to={'/auth/login'}
                            css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600`}
                        >
                            Already have an account? Login
                        </Link>
                    </div>
                </LoginFormContainer>
            )}
        </Formik>
    );
};

export default RegisterContainer;
