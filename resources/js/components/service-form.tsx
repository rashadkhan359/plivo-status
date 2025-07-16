import { useState } from 'react';
import { Input } from './ui/input';
import { Button } from './ui/button';
import { Select } from './ui/select';
import { Label } from './ui/label';

const STATUS_OPTIONS = [
  { value: 'operational', label: 'Operational' },
  { value: 'degraded', label: 'Degraded' },
  { value: 'partial_outage', label: 'Partial Outage' },
  { value: 'major_outage', label: 'Major Outage' },
];

export function ServiceForm({ initialValues = {}, onSubmit, loading: loadingProp }: {
  initialValues?: { name?: string; description?: string; status?: string };
  onSubmit: (values: any) => void;
  loading?: boolean;
}) {
  const [values, setValues] = useState({
    name: initialValues.name || '',
    description: initialValues.description || '',
    status: initialValues.status || 'operational',
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
        <Select
          value={values.status}
          onValueChange={handleStatusChange}
          disabled={loading || loadingProp}
        >
          {STATUS_OPTIONS.map((opt) => (
            <Select.Item key={opt.value} value={opt.value}>
              {opt.label}
            </Select.Item>
          ))}
        </Select>
      </div>
      {errors.form && <div className="text-red-500 text-xs mt-1">{errors.form}</div>}
      <Button type="submit" disabled={loading || loadingProp} className="w-full">
        {loading || loadingProp ? 'Saving...' : 'Save Service'}
      </Button>
    </form>
  );
} 