import { useState } from 'react';
import { Input } from './ui/input';
import { Button } from './ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Label } from './ui/label';

const STATUS_OPTIONS = [
  { value: 'operational', label: 'Operational' },
  { value: 'degraded', label: 'Degraded' },
  { value: 'partial_outage', label: 'Partial Outage' },
  { value: 'major_outage', label: 'Major Outage' },
];

const VISIBILITY_OPTIONS = [
  { value: 'public', label: 'Public' },
  { value: 'private', label: 'Private' },
];

export function ServiceForm({ initialValues = {}, onSubmit, loading: loadingProp }: {
  initialValues?: { 
    name?: string; 
    description?: string; 
    status?: string; 
    visibility?: string;
    team_id?: string;
    order?: number;
  };
  onSubmit: (values: any) => void;
  loading?: boolean;
}) {
  const [values, setValues] = useState({
    name: initialValues.name || '',
    description: initialValues.description || '',
    status: initialValues.status || 'operational',
    visibility: initialValues.visibility || 'public',
    team_id: initialValues.team_id || 'none',
    order: initialValues.order || 0,
  });
  const [errors, setErrors] = useState<any>({});
  const [loading, setLoading] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setValues((v) => ({ ...v, [e.target.name]: e.target.value }));
  };
  const handleStatusChange = (value: string) => {
    setValues((v) => ({ ...v, status: value }));
  };
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});
    // Simple validation
    if (!values.name) {
      setErrors({ name: 'Name is required' });
      setLoading(false);
      return;
    }
    try {
      await onSubmit(values);
    } catch (err: any) {
      setErrors(err?.response?.data?.errors || { form: 'An error occurred' });
    } finally {
      setLoading(false);
    }
  };
  return (
    <form onSubmit={handleSubmit} className="space-y-4 max-w-md w-full">
      <div>
        <Label htmlFor="name">Name</Label>
        <Input
          id="name"
          name="name"
          value={values.name}
          onChange={handleChange}
          disabled={loading || loadingProp}
          required
        />
        {errors.name && <div className="text-red-500 text-xs mt-1">{errors.name}</div>}
      </div>
      <div>
        <Label htmlFor="description">Description</Label>
        <Input
          id="description"
          name="description"
          value={values.description}
          onChange={handleChange}
          disabled={loading || loadingProp}
        />
      </div>
      <div>
        <Label htmlFor="status">Status</Label>
        <Select value={values.status} onValueChange={handleStatusChange} disabled={loading || loadingProp}>
          <SelectTrigger>
            <SelectValue placeholder="Select status" />
          </SelectTrigger>
          <SelectContent>
            {STATUS_OPTIONS.map((opt) => (
              <SelectItem key={opt.value} value={opt.value}>
                {opt.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
      <div>
        <Label htmlFor="visibility">Visibility</Label>
        <Select value={values.visibility} onValueChange={(value) => setValues(v => ({ ...v, visibility: value }))} disabled={loading || loadingProp}>
          <SelectTrigger>
            <SelectValue placeholder="Select visibility" />
          </SelectTrigger>
          <SelectContent>
            {VISIBILITY_OPTIONS.map((opt) => (
              <SelectItem key={opt.value} value={opt.value}>
                {opt.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
      <div>
        <Label htmlFor="order">Display Order</Label>
        <Input
          id="order"
          name="order"
          type="number"
          value={values.order}
          onChange={(e) => setValues(v => ({ ...v, order: parseInt(e.target.value) || 0 }))}
          disabled={loading || loadingProp}
        />
      </div>
      {errors.form && <div className="text-red-500 text-xs mt-1">{errors.form}</div>}
      <Button type="submit" disabled={loading || loadingProp} className="w-full">
        {loading || loadingProp ? 'Saving...' : 'Save Service'}
      </Button>
    </form>
  );
} 