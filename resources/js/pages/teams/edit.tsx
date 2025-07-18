import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Users } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Team } from '@/types';

interface TeamEditProps {
    team: Team;
}

type TeamForm = {
    name: string;
    description: string | null;
    color: string | null;
};

export default function TeamEdit({ team }: TeamEditProps) {
    const { data, setData, put, processing, errors, reset } = useForm<TeamForm>({
        name: team.name,
        description: team.description || '',
        color: team.color || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('teams.update', team.id), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <Head title={`Edit Team - ${team.name}`} />
            
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">Edit Team</h1>
                    <p className="text-muted-foreground">
                        Update team information and settings.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            Team Information
                        </CardTitle>
                        <CardDescription>
                            Update your team's basic information.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="name">Team Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    disabled={processing}
                                    placeholder="Enter team name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description || ''}
                                    onChange={(e) => setData('description', e.target.value)}
                                    disabled={processing}
                                    placeholder="Enter team description"
                                    rows={3}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="color">Team Color (Optional)</Label>
                                <div className="flex items-center space-x-2">
                                    <Input
                                        id="color"
                                        type="color"
                                        value={data.color || '#3B82F6'}
                                        onChange={(e) => setData('color', e.target.value)}
                                        disabled={processing}
                                        className="w-16 h-10 p-1"
                                    />
                                    <Input
                                        type="text"
                                        value={data.color || ''}
                                        onChange={(e) => setData('color', e.target.value)}
                                        disabled={processing}
                                        placeholder="#3B82F6"
                                        className="flex-1"
                                    />
                                </div>
                                <InputError message={errors.color} />
                                <p className="text-xs text-muted-foreground">
                                    Choose a color to represent this team.
                                </p>
                            </div>

                            <div className="flex justify-end space-x-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => reset()}
                                    disabled={processing}
                                >
                                    Reset
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                                    Update Team
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
} 