import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    AlertCircle, 
    Clock, 
    CheckCircle, 
    Search, 
    Mail, 
    ArrowLeft,
    Users,
    Calendar
} from 'lucide-react';

interface InvitationErrorProps {
    error: 'not_found' | 'expired' | 'already_accepted';
    message: string;
    invitation?: {
        organization: {
            name: string;
        };
        invited_by: {
            name: string;
        };
        expires_at?: string;
    };
}

export default function InvitationError({ error, message, invitation }: InvitationErrorProps) {
    const getErrorConfig = () => {
        switch (error) {
            case 'not_found':
                return {
                    icon: <Search className="h-12 w-12 text-muted-foreground" />,
                    title: 'Invitation Not Found',
                    description: 'The invitation link you\'re looking for doesn\'t exist or may have been removed.',
                    color: 'text-muted-foreground',
                    bgColor: 'bg-muted',
                    action: {
                        text: 'Return to Home',
                        href: route('home'),
                        icon: <ArrowLeft className="h-4 w-4" />
                    }
                };
            case 'expired':
                return {
                    icon: <Clock className="h-12 w-12 text-orange-500" />,
                    title: 'Invitation Expired',
                    description: 'This invitation has expired and is no longer valid.',
                    color: 'text-orange-600',
                    bgColor: 'bg-orange-50 dark:bg-orange-950/20',
                    action: {
                        text: 'Contact Administrator',
                        href: `mailto:${invitation?.invited_by.name}`,
                        icon: <Mail className="h-4 w-4" />
                    }
                };
            case 'already_accepted':
                return {
                    icon: <CheckCircle className="h-12 w-12 text-green-500" />,
                    title: 'Invitation Already Accepted',
                    description: 'This invitation has already been accepted and is no longer valid.',
                    color: 'text-green-600',
                    bgColor: 'bg-green-50 dark:bg-green-950/20',
                    action: {
                        text: 'Go to Dashboard',
                        href: route('login'),
                        icon: <Users className="h-4 w-4" />
                    }
                };
            default:
                return {
                    icon: <AlertCircle className="h-12 w-12 text-red-500" />,
                    title: 'Error',
                    description: message,
                    color: 'text-red-600',
                    bgColor: 'bg-red-50 dark:bg-red-950/20',
                    action: {
                        text: 'Return to Home',
                        href: route('home'),
                        icon: <ArrowLeft className="h-4 w-4" />
                    }
                };
        }
    };

    const config = getErrorConfig();

    return (
        <div className="min-h-screen flex items-center justify-center bg-background py-12 px-4 sm:px-6 lg:px-8">
            <Head title={config.title} />
            
            <div className="max-w-md w-full space-y-8">
                <div className="text-center">
                    <div className={`mx-auto w-24 h-24 ${config.bgColor} rounded-full flex items-center justify-center mb-6`}>
                        {config.icon}
                    </div>
                    <h1 className={`text-3xl font-bold ${config.color} mb-2`}>
                        {config.title}
                    </h1>
                    <p className="text-muted-foreground text-lg">
                        {config.description}
                    </p>
                </div>

                {invitation && (
                    <Card className="border-dashed">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Invitation Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between items-center">
                                <span className="text-sm text-muted-foreground">Organization</span>
                                <span className="font-medium">{invitation.organization.name}</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-sm text-muted-foreground">Invited by</span>
                                <span className="font-medium">{invitation.invited_by.name}</span>
                            </div>
                            {invitation.expires_at && (
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-muted-foreground">Expired on</span>
                                    <div className="flex items-center gap-1">
                                        <Calendar className="h-3 w-3" />
                                        <span className="text-sm">
                                            {new Date(invitation.expires_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-4">
                    <Button asChild className="w-full" size="lg">
                        <Link href={config.action.href}>
                            {config.action.icon}
                            {config.action.text}
                        </Link>
                    </Button>
                    
                    <Button asChild variant="outline" className="w-full">
                        <Link href={route('home')}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Return to Home
                        </Link>
                    </Button>
                </div>

                {error === 'expired' && (
                    <Card className="bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800">
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-3">
                                <Mail className="h-5 w-5 text-blue-600 mt-0.5" />
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-blue-900 dark:text-blue-100">
                                        Need a new invitation?
                                    </p>
                                    <p className="text-sm text-blue-700 dark:text-blue-300">
                                        Contact {invitation?.invited_by.name} to request a fresh invitation link.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {error === 'already_accepted' && (
                    <Card className="bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800">
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-3">
                                <CheckCircle className="h-5 w-5 text-green-600 mt-0.5" />
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-green-900 dark:text-green-100">
                                        Already have access?
                                    </p>
                                    <p className="text-sm text-green-700 dark:text-green-300">
                                        You can log in to your account to access {invitation?.organization.name}.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </div>
    );
} 